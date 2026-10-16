<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Costs\Models\CostProfile;
use App\Modules\Operations\Models\StockBalance;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CatalogItem extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['unit_price'];

    protected $hidden = ['current_cost_id', 'currentCost'];

    public const UNITS = ['unit' => 'Unidad', 'hour' => 'Hora', 'day' => 'Día', 'package' => 'Paquete'];

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }

    public function currentCost(): BelongsTo
    {
        return $this->belongsTo(CostProfile::class, 'current_cost_id');
    }

    protected function casts(): array
    {
        return ['price_minor' => 'decimal:0', 'is_active' => 'boolean', 'can_buy' => 'boolean', 'can_sell' => 'boolean', 'track_stock' => 'boolean'];
    }

    public function stock(): HasOne
    {
        return $this->hasOne(StockBalance::class, 'catalog_item_id');
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::get(fn () => bcdiv((string) $this->price_minor, $this->currency === 'USD' ? '100' : '1', $this->currency === 'USD' ? 2 : 0));
    }

    public function quoteDescription(): string
    {
        return $this->description ?: $this->name;
    }

    public function selectionLabel(): string
    {
        return ($this->sku ? $this->sku.' · ' : '').$this->name.' — '.Quote::money($this->price_minor, $this->currency);
    }
}
