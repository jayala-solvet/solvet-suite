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
            $user = app(AccessService::class)->createWithPassword(auth()->user(), $data);
            $this->data['new_password'] = null;
            $this->data['new_password_confirmation'] = null;

            return $user;
        } catch (ValidationException $exception) {
            $this->data['new_password'] = null;
            $this->data['new_password_confirmation'] = null;
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all()
            );
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->data['new_password'] = null;
        $this->data['new_password_confirmation'] = null;
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
