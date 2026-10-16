<?php

namespace App\Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';

    protected $guarded = ['id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['lines' => 'array', 'purchase_date' => 'date', 'received_at' => 'datetime', 'total_minor' => 'decimal:0', 'freight_minor' => 'decimal:0'];
    }

    public function referenceLabel(): string
    {
        return 'COMP-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
