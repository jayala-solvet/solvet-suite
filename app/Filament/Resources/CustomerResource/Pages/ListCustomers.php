<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected ?string $subheading = 'Tu cartera de clientes y sus datos de contacto, en un solo lugar.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo cliente')->icon('heroicon-o-plus')];
    }
}
