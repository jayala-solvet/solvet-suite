<?php

namespace App\Modules\Legal\Models;

use Illuminate\Database\Eloquent\Model;

class LegalEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function document()
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('La evidencia no se modifica.'));
        static::deleting(fn () => throw new \LogicException('La evidencia no se elimina.'));
    }
}
