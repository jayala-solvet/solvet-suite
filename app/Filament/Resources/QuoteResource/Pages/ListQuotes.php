<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected ?string $subheading = 'Prepará propuestas, emití documentos y seguí cada decisión.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo presupuesto')->icon('heroicon-o-plus')];
    }
}
