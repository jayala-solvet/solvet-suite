<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected static ?string $title = 'Nuevo presupuesto';

    protected ?string $subheading = 'Completá los datos de tu propuesta. Se guardará como borrador.';

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Guardar borrador');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Guardar y crear otro');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(QuoteService::class)->saveDraft(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
