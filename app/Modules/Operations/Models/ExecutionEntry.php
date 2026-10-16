<?php

namespace App\Modules\Operations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionEntry extends Model
{
    protected $table = 'execution_entries';

    protected $guarded = ['id'];

    protected $hidden = ['cost_minor', 'request_hash'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return ['voided_at' => 'datetime', 'quantity' => 'decimal:3', 'cost_minor' => 'decimal:8'];
    }
}
