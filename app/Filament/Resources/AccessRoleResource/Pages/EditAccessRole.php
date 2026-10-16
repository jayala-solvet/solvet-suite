<?php

namespace App\Filament\Resources\AccessRoleResource\Pages;

use App\Filament\Resources\AccessRoleResource;
use App\Modules\Access\Services\AccessService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAccessRole extends EditRecord
{
    protected static string $resource = AccessRoleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AccessService::class)->saveRole(auth()->user(), $data, $record, $data['current_password']);
    }

    protected function getRedirectUrl(): string
    {
        return AccessRoleResource::getUrl('index');
    }
}
