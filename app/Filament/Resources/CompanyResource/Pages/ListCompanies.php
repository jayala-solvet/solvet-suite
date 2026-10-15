<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'Mi empresa';

    protected ?string $subheading = 'Administrá la información de tu empresa emisora.';
}
