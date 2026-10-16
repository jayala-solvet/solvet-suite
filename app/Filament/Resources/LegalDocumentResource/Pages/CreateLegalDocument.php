<?php

namespace App\Filament\Resources\LegalDocumentResource\Pages;

use App\Filament\Resources\LegalDocumentResource;
use App\Modules\Legal\Services\LegalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLegalDocument extends CreateRecord
{
    protected static string $resource = LegalDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LegalService::class)->draft(auth()->user(), $data, $data['current_password']);
    }

    protected function getRedirectUrl(): string
    {
        return LegalDocumentResource::getUrl('index');
    }
}
