<?php

namespace App\Filament\Pages;

use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Models\Quote;
use Filament\Pages\Page;

class Workspace extends Page
{
    protected string $view = 'filament.pages.workspace';

    protected static ?string $title = 'Centro de trabajo';

    protected static ?string $navigationLabel = 'Vista general';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = -1;

    public function summary(): array
    {
        return ['draft' => Quote::where('status', 'draft')->count(), 'issued' => Quote::where('status', 'issued')->count(), 'accepted' => Quote::where('status', 'accepted')->count(), 'customers' => Customer::count()];
    }

    public function recent()
    {
        return Quote::with('customer')->latest()->limit(6)->get();
    }
}
