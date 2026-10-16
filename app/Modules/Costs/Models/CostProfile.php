<?php

namespace App\Modules\Costs\Models;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostProfile extends Model
{
    protected $guarded = ['id'];

    public const SOURCES = ['purchase' => 'Producto de distribuidor', 'own' => 'Servicio propio', 'external' => 'Servicio de tercero'];

    public const MODES = ['margin' => 'Margen sobre venta (%)', 'markup' => 'Recargo sobre costo (%)', 'fixed_profit' => 'Utilidad fija por unidad'];

    public const VAT = ['recoverable' => 'IVA recuperable previsto', 'non_recoverable' => 'IVA no recuperable', 'pending' => 'Pendiente de determinar'];

    protected function casts(): array
    {
        return ['purchase_minor' => 'decimal:0', 'extra_minor' => 'decimal:0', 'effective_unit_minor' => 'decimal:8', 'pricing_value' => 'decimal:2', 'suggested_price_minor' => 'decimal:0', 'cost_date' => 'date', 'valid_until' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function selectionLabel(): string
    {
        $amount = $this->effective_unit_minor === null ? 'IVA pendiente' : Quote::money(CostCalculator::round($this->effective_unit_minor), $this->currency);

        return '#'.$this->id.' · '.self::SOURCES[$this->source].' · '.($this->supplier_name ?? 'Trabajo propio').' · '.$amount.($this->expired() ? ' · Vencido' : '');
    }

    public function expired(): bool
    {
        return $this->valid_until?->isBefore(today()) ?? false;
    }
}
