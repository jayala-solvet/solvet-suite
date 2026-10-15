<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'Editar mi empresa';

    protected ?string $subheading = 'Los documentos ya emitidos conservan sus datos originales.';
}
