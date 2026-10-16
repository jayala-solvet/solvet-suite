<?php

namespace App\Filament\Resources\AccessRoleResource\Pages;

use App\Filament\Resources\AccessRoleResource;
use App\Modules\Access\Services\AccessService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccessRole extends CreateRecord
{
    protected static string $resource = AccessRoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AccessService::class)->saveRole(auth()->user(), $data, null, $data['current_password']);
    }

    protected function getRedirectUrl(): string
    {
        return AccessRoleResource::getUrl('index');
    }
}
