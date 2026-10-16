<?php

namespace App\Filament\Resources\AccessRoleResource\Pages;

use App\Filament\Resources\AccessRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccessRoles extends ListRecords
{
    protected static string $resource = AccessRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear')];
    }
}
