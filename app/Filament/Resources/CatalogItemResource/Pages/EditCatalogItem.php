<?php

namespace App\Filament\Resources\CatalogItemResource\Pages;

use App\Filament\Resources\CatalogItemResource;
use App\Modules\Catalog\Services\CatalogService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCatalogItem extends EditRecord
{
    protected static string $resource = CatalogItemResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CatalogService::class)->save(auth()->user(), $data, $record);
    }

    protected function getRedirectUrl(): string
    {
        return CatalogItemResource::getUrl('index');
    }
}
