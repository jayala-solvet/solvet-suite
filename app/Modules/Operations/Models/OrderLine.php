<?php

namespace App\Modules\Operations\Models;

use App\Modules\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $table = 'order_lines';

    protected $guarded = ['id'];

    protected $hidden = ['estimated_unit_minor', 'source', 'cost_profile_id'];

    protected function casts(): array
    {
        return ['track_stock' => 'boolean', 'quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'done_quantity' => 'decimal:3', 'estimated_unit_minor' => 'decimal:8'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }
}
