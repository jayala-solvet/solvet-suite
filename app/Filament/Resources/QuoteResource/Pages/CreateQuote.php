<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(QuoteService::class)->saveDraft(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
