<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Quotes\Models\Quote;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected ?string $subheading = 'Prepará propuestas, emití documentos y seguí cada decisión.';

    public function getTabs(): array
    {
        $counts = Quote::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $tabs = ['all' => Tab::make('Todos')->badge($counts->sum())];
        foreach (['draft' => 'Borradores', 'issued' => 'Emitidos', 'accepted' => 'Aceptados', 'rejected' => 'Rechazados', 'cancelled' => 'Anulados'] as $state => $label) {
            $tabs[$state] = Tab::make($label)->badge($counts[$state] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $state));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo presupuesto')->icon('heroicon-o-plus')];
    }
}
