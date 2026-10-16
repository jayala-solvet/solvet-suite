<?php

namespace App\Modules\Operations\Models;

use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $guarded = ['id'];

    protected $hidden = ['estimated_snapshot'];

    protected function casts(): array
    {
        return ['assigned_to' => 'integer', 'estimated_snapshot' => 'array', 'completed_at' => 'datetime', 'sales_minor' => 'decimal:0', 'sales_tax_minor' => 'decimal:0'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function reference(): string
    {
        return 'PED-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
