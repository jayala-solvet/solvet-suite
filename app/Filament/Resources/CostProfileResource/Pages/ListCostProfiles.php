<?php

namespace App\Filament\Resources\CostProfileResource\Pages;

use App\Filament\Resources\CostProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCostProfiles extends ListRecords
{
    protected static string $resource = CostProfileResource::class;

    protected ?string $subheading = 'Costos internos, proveedores y reglas de precio. El historial conserva cada versión.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo costo')];
    }
}
