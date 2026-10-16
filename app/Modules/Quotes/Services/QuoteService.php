<?php

namespace App\Modules\Quotes\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Costs\Services\QuoteProfitability;
use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(private QuoteCalculator $calculator) {}

    private function authorize(User $actor, string $permission): void
    {
        Gate::forUser($actor)->authorize($permission);
    }

    private function event(User $actor, Quote $quote, string $action, array $metadata = []): void
    {
        AuditEvent::create(['actor_id' => $actor->id, 'action' => $action, 'subject_type' => 'quote', 'subject_id' => $quote->id, 'metadata' => $metadata]);
    }

    public function saveDraft(User $actor, array $data, ?Quote $quote = null): Quote
    {
        $this->authorize($actor, $quote ? 'quotes.update' : 'quotes.create');
        $data = Validator::make($data, ['customer_id' => 'required|exists:customers,id', 'subject' => 'required|string|max:200', 'currency' => 'required|in:PYG,USD', 'valid_until' => 'required|date|after_or_equal:today', 'terms' => 'nullable|string|max:5000',
            'payment_mode' => 'sometimes|required|in:cash,credit,to_agree',
            'credit_days' => 'nullable|required_if:payment_mode,credit|integer|min:1|max:365',
            'payment_notes' => 'nullable|string|max:1000', 'delivery_terms' => 'nullable|string|max:500',
            'warranty_terms' => 'nullable|string|max:500',
            'lines' => 'required|array|min:1|max:100'])->validate();
        $data['payment_mode'] ??= $quote ? ($quote->payment_mode ?? 'to_agree') : 'cash';
        Validator::make(['lines' => $data['lines']], ['lines.*' => 'required|array', 'lines.*.catalog_item_id' => 'nullable|integer|exists:catalog_items,id'])->validate();
        if ($data['payment_mode'] === 'credit' && empty($data['credit_days'])) {
            throw ValidationException::withMessages(['credit_days' => 'Indicá el plazo del crédito.']);
        }
        $data['credit_days'] = $data['payment_mode'] === 'credit' ? $data['credit_days'] : null;
        if (! $quote && ! array_key_exists('terms', $data)) {
            $data['terms'] = QuoteDefaults::terms();
        }
        $items = CatalogItem::whereIn('id', array_filter(array_column($data['lines'], 'catalog_item_id')))->get()->keyBy('id');
        foreach ($data['lines'] as $key => &$line) {
            $item = $items->get($line['catalog_item_id'] ?? null);
            if ($item && (! $item->can_sell || $item->currency !== $data['currency'])) {
                throw ValidationException::withMessages(['lines.'.$key.'.catalog_item_id' => 'El ítem de catálogo usa otra moneda. Elegí uno de la moneda del presupuesto o cargá el ítem manualmente.']);
            }
            $line['sku'] = $item?->sku;
        }
        unset($line);
        if (! $actor->allows('costs.view')) {
            foreach ($data['lines'] as &$line) {
                unset($line['cost_profile_id'],$line['internal_unit_cost'],$line['components']);
            }
        } unset($line);
        $calculated = $this->calculator->calculate($data['lines'], $data['currency']);

        return DB::transaction(function () use ($actor, $data, $calculated, $quote) {
            if ($quote) {
                $quote = Quote::lockForUpdate()->findOrFail($quote->id);
                if ($quote->status !== 'draft') {
                    throw ValidationException::withMessages(['status' => 'Solo se pueden editar borradores.']);
                }
            } else {
                $quote = new Quote(['created_by' => $actor->id, 'status' => 'draft']);
            }
            if ($quote->exists && $quote->currency === $data['currency']) {
                foreach ($data['lines'] as $index => &$line) {
                    $previous = $quote->cost_snapshot['lines'][$index] ?? null;
                    if ($previous && (string) ($quote->lines[$index]['catalog_item_id'] ?? '') === (string) ($line['catalog_item_id'] ?? '')) {
                        $line = QuoteProfitability::restoreInputs($line, $previous, $data['currency']);
                    }
                }
                unset($line);
            }
            $costSnapshot = app(QuoteProfitability::class)->forQuote($actor, $data['lines'], $calculated, $data['currency']);
            $quote->fill(array_merge($data, $calculated, ['cost_snapshot' => $costSnapshot]))->save();
            $this->event($actor, $quote, 'draft.saved');

            return $quote;
        });
    }

    public function issue(User $actor, Quote $quote): Quote
    {
        $this->authorize($actor, 'quotes.approve');

        return DB::transaction(function () use ($actor, $quote) {
            $quote = Quote::lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'El presupuesto ya fue emitido.']);
            }
            if ($quote->valid_until->isBefore(today())) {
                throw ValidationException::withMessages(['valid_until' => 'Actualizá la fecha de vigencia antes de emitir.']);
            }
            $seq = DB::table('document_sequences')->where('key', 'quote')->lockForUpdate()->first();
            if (! $seq) {
                throw new \RuntimeException('Ejecutá solvet:install antes de emitir.');
            }
            $customer = Customer::findOrFail($quote->customer_id);
            $company = Company::firstOrFail();
            $quote->fill(['number' => $seq->next_number, 'status' => 'issued', 'issued_at' => now(), 'snapshot' => ['company' => $company->only(['name', 'tax_id', 'email', 'phone', 'address']), 'customer' => $customer->only(['name', 'tax_id', 'contact', 'email', 'phone', 'address'])]]);
            $bytes = Pdf::loadView('pdf.quote', ['quote' => $quote, 'draft' => false])->setPaper('a4')->output();
            $path = 'quotes/'.Str::uuid().'.pdf';
            Storage::disk('local')->put($path, $bytes);
            $quote->fill(['pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes)])->save();
            DB::table('document_sequences')->where('key', 'quote')->update(['next_number' => $seq->next_number + 1]);
            $this->event($actor, $quote, 'quote.issued', ['number' => $quote->number, 'pdf_sha256' => $quote->pdf_sha256]);

            return $quote;
        });
    }

    public function deleteDraft(User $actor, Quote $quote): void
    {
        $this->authorize($actor, 'quotes.delete');
        DB::transaction(function () use ($actor, $quote) {
            $quote = Quote::lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== 'draft' || $quote->number !== null) {
                throw ValidationException::withMessages(['status' => 'Solo se pueden eliminar borradores sin emitir.']);
            }
            $this->event($actor, $quote, 'draft.deleted', ['reference' => $quote->reference()]);
            $quote->delete();
        });
    }

    public function decide(User $actor, Quote $quote, string $status, string $evidence): Quote
    {
        $this->authorize($actor, 'quotes.approve');
        Validator::make(['status' => $status, 'evidence' => $evidence], ['status' => 'required|in:accepted,rejected,cancelled', 'evidence' => 'required|string|min:10|max:2000'])->validate();

        return DB::transaction(function () use ($actor, $quote, $status, $evidence) {
            $quote = Quote::lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== 'issued') {
                throw ValidationException::withMessages(['status' => 'Solo un presupuesto emitido puede recibir esta decisión.']);
            }
            $quote->update(['status' => $status]);
            $this->event($actor, $quote, 'quote.'.$status, ['evidence' => $evidence]);

            return $quote;
        });
    }
}
