<?php

namespace App\Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $table = 'stock_balances';

    protected $guarded = ['id'];

    protected $hidden = ['value_minor'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'reserved' => 'decimal:3', 'value_minor' => 'decimal:8'];
    }

    protected $primaryKey = 'catalog_item_id';

    public $incrementing = false;

    public function available(): string
    {
        return bcsub($this->quantity, $this->reserved, 3);
    }

    public function average(): string
    {
        return bccomp($this->quantity, '0', 3) > 0 ? bcdiv($this->value_minor, $this->quantity, 8) : '0';
    }
}
