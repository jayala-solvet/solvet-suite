<?php

namespace App\Filament\Resources\CatalogItemResource\Pages;

use App\Filament\Resources\CatalogItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogItems extends ListRecords
{
    protected static string $resource = CatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo producto o servicio')];
    }
}
