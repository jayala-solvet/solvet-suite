<?php

namespace App\Modules\Legal\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['controller_snapshot' => 'array', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function ($record) {
            if ($record->getOriginal('published_at')) {
                throw new \LogicException('Una versión publicada es inmutable. Creá otra versión.');
            }
        });
        static::deleting(fn () => throw new \LogicException('No se eliminan versiones legales.'));
    }
}
