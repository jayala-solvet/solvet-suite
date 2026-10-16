<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Modules\Access\Services\AccessService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AccessService::class)->save(auth()->user(), $data, $record, $data['current_password']);
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
