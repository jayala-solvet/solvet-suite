<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Modules\Access\Services\AccessService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AccessService::class)->save(auth()->user(), $data, null, $data['current_password']);
        } catch (ValidationException $exception) {
            $this->data['current_password'] = null;
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all()
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
