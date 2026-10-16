<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Modules\Operations\Services\PurchaseService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    public static bool $formActionsAreSticky = true;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PurchaseService::class)->save(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return PurchaseResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
