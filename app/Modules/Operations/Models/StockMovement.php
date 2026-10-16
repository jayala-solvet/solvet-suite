<?php

namespace App\Modules\Operations\Models;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $guarded = ['id'];

    protected $hidden = ['value_minor', 'unit_cost_minor', 'request_hash'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'returned_quantity' => 'decimal:3', 'value_minor' => 'decimal:8', 'unit_cost_minor' => 'decimal:8'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }
}
