<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class OperationAction
{
    public static function run(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (ValidationException $e) {
            Notification::make()->title('No se pudo completar la operación')->body(collect($e->errors())->flatten()->first())->danger()->send();
            throw new Halt;
        }
    }
}
