<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Costs\Models\CostProfile;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['unit_price'];

    protected $hidden = ['current_cost_id', 'currentCost'];

    public function currentCost(): BelongsTo
    {
        return $this->belongsTo(CostProfile::class, 'current_cost_id');
    }

    protected function casts(): array
    {
        return ['price_minor' => 'decimal:0', 'is_active' => 'boolean', 'can_purchase' => 'boolean', 'can_sell' => 'boolean', 'track_stock' => 'boolean'];
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
