<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Modules\Access\Services\AccessService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(AccessService::class)->save(auth()->user(), $data, $record, $data['current_password']);
        } catch (ValidationException $exception) {
            $this->data['current_password'] = null;
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all()
            );
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $r = $this->getRecord();
        $data['status'] = $r->trashed() ? 'deleted' : ($r->is_active ? 'active' : 'suspended');

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
