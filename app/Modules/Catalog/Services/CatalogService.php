<?php

namespace App\Modules\Catalog\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Quotes\Services\QuoteCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function save(User $actor, array $data, ?CatalogItem $item = null): CatalogItem
    {
        Gate::forUser($actor)->authorize('write-business');
        $data['sku'] = trim($data['sku'] ?? '') ?: null;
        $data = Validator::make($data, [
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('catalog_items')->ignore($item?->id)],
            'name' => 'required|string|max:200', 'description' => 'nullable|string|max:1000',
            'kind' => 'required|in:product,service', 'currency' => 'required|in:PYG,USD',
            'unit_price' => 'required', 'tax_rate' => 'required|in:0,5,10', 'is_active' => 'required|boolean',
        ])->validate();
        try {
            $calculated = app(QuoteCalculator::class)->calculate([['description' => $data['name'], 'quantity' => '1', 'unit_price' => $data['unit_price'], 'discount' => '0', 'tax_rate' => (string) $data['tax_rate']]], $data['currency']);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['unit_price' => collect($e->errors())->flatten()->first()]);
        }
        unset($data['unit_price']);

        return DB::transaction(function () use ($actor, $data, $calculated, $item) {
            $item ??= new CatalogItem;
            $new = ! $item->exists;
            $item->fill($data + ['price_minor' => $calculated['total_minor']])->save();
            AuditEvent::create(['actor_id' => $actor->id, 'action' => $new ? 'catalog.created' : 'catalog.updated', 'subject_type' => 'catalog_item', 'subject_id' => $item->id, 'metadata' => ['sku' => $item->sku, 'is_active' => $item->is_active]]);

            return $item;
        });
    }
}
