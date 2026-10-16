<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function label(): string
    {
        return ['draft.deleted' => 'Borrador eliminado', 'draft.saved' => 'Borrador guardado', 'quote.issued' => 'Presupuesto emitido',
            'quote.accepted' => 'Aceptación registrada', 'quote.rejected' => 'Rechazo registrado',
            'quote.cancelled' => 'Anulación registrada'][$this->action] ?? $this->action;
    }

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }
}
