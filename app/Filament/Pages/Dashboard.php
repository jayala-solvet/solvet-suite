<?php

namespace App\Filament\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Dashboard\Services\CommercialDashboard;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Resumen comercial';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 0;

    #[Url]
    public string $currency = 'PYG';

    #[Url]
    public string $period = 'month';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->allows('dashboard.view') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('createQuote')->label('Nuevo presupuesto')->icon('heroicon-o-plus')
            ->url(QuoteResource::getUrl('create'))->visible(fn () => auth()->user()?->canWrite() ?? false)];
    }

    public function report(): array
    {
        return app(CommercialDashboard::class)->report(auth()->user(), $this->currency, $this->period);
    }
}
