<?php

namespace App\Modules\Costs\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Costs\Models\CostProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CostService
{
    public function create(User $actor, array $data): CostProfile
    {
        Gate::forUser($actor)->authorize('manage-costs');
        $data = Validator::make($data, [
            'catalog_item_id' => 'required|integer|exists:catalog_items,id',
            'source' => 'required|in:purchase,own,external', 'supplier_name' => 'nullable|required_unless:source,own|string|max:200',
            'supplier_tax_id' => 'nullable|string|max:30', 'reference' => 'nullable|string|max:200',
            'unit_cost' => 'required', 'extra_cost' => 'required', 'purchase_tax_rate' => 'required|in:0,5,10',
            'vat_treatment' => 'required|in:recoverable,non_recoverable,pending',
            'pricing_mode' => 'required|in:margin,markup,fixed_profit', 'pricing_value' => 'required',
            'cost_date' => 'required|date|before_or_equal:today', 'valid_until' => 'nullable|date|after_or_equal:today|after_or_equal:cost_date',
            'notes' => 'nullable|string|max:1000', 'apply_price' => 'required|boolean',
        ])->validate();

        return DB::transaction(function () use ($actor, $data) {
            $item = CatalogItem::lockForUpdate()->findOrFail($data['catalog_item_id']);
            if (($item->kind === 'product' && $data['source'] !== 'purchase') || ($item->kind === 'service' && $data['source'] === 'purchase')) {
                throw ValidationException::withMessages(['source' => 'El origen del costo debe corresponder al tipo de producto o servicio.']);
            }
            $values = app(CostCalculator::class)->calculate($data, $item->currency, (int) $item->tax_rate);
            if ($data['apply_price'] && $values['suggested_price_minor'] === null) {
                throw ValidationException::withMessages(['apply_price' => 'Definí el tratamiento del IVA antes de aplicar un precio.']);
            }
            $apply = $data['apply_price'];
            unset($data['unit_cost'], $data['extra_cost'], $data['apply_price']);
            if ($data['source'] === 'own') {
                $data['supplier_name'] = $data['supplier_tax_id'] = null;
            }
            $profile = CostProfile::create($data + $values + ['created_by' => $actor->id, 'unit' => $item->unit, 'currency' => $item->currency, 'sales_tax_rate' => $item->tax_rate]);
            $before = ['current_cost_id' => $item->current_cost_id, 'price_minor' => $item->price_minor];
            $item->current_cost_id = $profile->id;
            if ($apply) {
                $item->price_minor = $profile->suggested_price_minor;
            }
            $item->save();
            AuditEvent::create(['actor_id' => $actor->id, 'action' => 'cost.created', 'subject_type' => 'cost_profile', 'subject_id' => $profile->id,
                'metadata' => ['catalog_item_id' => $item->id, 'apply_price' => $apply, 'before' => $before, 'after' => ['current_cost_id' => $profile->id, 'price_minor' => $item->price_minor]]]);

            return $profile;
        });
    }
}
