<?php

namespace App\Modules\Access\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRole extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'system' => 'boolean'];
    }
}
