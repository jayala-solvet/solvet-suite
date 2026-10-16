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
            'extra_tax_rate' => 'sometimes|in:0,5,10', 'extra_vat_treatment' => 'sometimes|in:recoverable,non_recoverable', 'pricing_mode' => 'required|in:margin,markup,fixed_profit,manual', 'pricing_value' => 'required',
            'cost_date' => 'required|date|before_or_equal:today', 'valid_until' => 'nullable|date|after_or_equal:today|after_or_equal:cost_date',
            'notes' => 'nullable|string|max:1000', 'apply_price' => 'sometimes|boolean',
        ])->validate();

        return DB::transaction(function () use ($actor, $data) {
            $item = CatalogItem::lockForUpdate()->findOrFail($data['catalog_item_id']);
            if (($item->kind === 'product' && $data['source'] !== 'purchase') || ($item->kind === 'service' && $data['source'] === 'purchase')) {
                throw ValidationException::withMessages(['source' => 'El origen del costo debe corresponder al tipo de producto o servicio.']);
            }
            if ($data['source'] !== 'own' && ! $item->can_buy) {
                throw ValidationException::withMessages(['source' => 'El ítem no está habilitado para compras.']);
            }
            $values = app(CostCalculator::class)->calculate($data, $item->currency, (int) $item->tax_rate);
            if ($data['apply_price'] ?? false) {
                throw ValidationException::withMessages(['apply_price' => 'Guardá la referencia y usá la acción Aplicar precio al catálogo.']);
            }
            $apply = false;
            unset($data['unit_cost'], $data['extra_cost'], $data['apply_price']);
            if ($data['source'] === 'own') {
                $data['supplier_name'] = $data['supplier_tax_id'] = null;
            }
            $profile = CostProfile::create($data + $values + ['created_by' => $actor->id, 'unit' => $item->unit, 'currency' => $item->currency, 'sales_tax_rate' => $item->tax_rate]);
            $before = ['current_cost_id' => $item->current_cost_id, 'price_minor' => $item->price_minor];
            AuditEvent::create(['actor_id' => $actor->id, 'action' => 'cost.created', 'subject_type' => 'cost_profile', 'subject_id' => $profile->id,
                'metadata' => ['catalog_item_id' => $item->id, 'apply_price' => $apply, 'before' => $before, 'after' => ['current_cost_id' => $item->current_cost_id, 'price_minor' => $item->price_minor]]]);

            return $profile;
        });
    }

    public function applyPrice(User $actor, CostProfile $profile): void
    {
        Gate::forUser($actor)->authorize('costs.approve');
        DB::transaction(function () use ($actor, $profile) {
            $profile->refresh();
            $item = CatalogItem::lockForUpdate()->findOrFail($profile->catalog_item_id);
            if ($item->currency !== $profile->currency || $item->unit !== $profile->unit || $profile->suggested_price_minor === null || $profile->expired()) {
                throw ValidationException::withMessages(['operation' => 'Revisá moneda, unidad, vigencia y tratamiento del IVA antes de aplicar el precio.']);
            }
            $price = app(CostCalculator::class)->priceForProfile($profile, (int) $item->tax_rate);
            $before = $item->price_minor;
            $item->update(['current_cost_id' => $profile->id, 'price_minor' => $price]);
            AuditEvent::create(['actor_id' => $actor->id, 'action' => 'cost.price_applied', 'subject_type' => 'cost_profile', 'subject_id' => $profile->id, 'metadata' => ['before' => $before, 'after' => $price, 'catalog_item_id' => $item->id]]);
        });
    }
}
