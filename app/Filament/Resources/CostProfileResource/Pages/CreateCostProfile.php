<?php

namespace App\Filament\Resources\CostProfileResource\Pages;

use App\Filament\Resources\CostProfileResource;
use App\Modules\Costs\Services\CostService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCostProfile extends CreateRecord
{
    protected static string $resource = CostProfileResource::class;

    protected static ?string $title = 'Nueva versión de costo';

    public static bool $formActionsAreSticky = true;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CostService::class)->create(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return CostProfileResource::getUrl('index');
    }
}
