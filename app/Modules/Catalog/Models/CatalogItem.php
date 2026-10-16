<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['unit_price'];

    protected function casts(): array
    {
        return ['price_minor' => 'decimal:0', 'is_active' => 'boolean'];
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
