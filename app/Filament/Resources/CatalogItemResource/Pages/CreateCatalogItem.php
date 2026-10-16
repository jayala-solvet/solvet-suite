<?php

namespace App\Filament\Resources\CatalogItemResource\Pages;

use App\Filament\Resources\CatalogItemResource;
use App\Modules\Catalog\Services\CatalogService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCatalogItem extends CreateRecord
{
    protected static string $resource = CatalogItemResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CatalogService::class)->save(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return CatalogItemResource::getUrl('index');
    }
}
