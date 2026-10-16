<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Costs\Services\QuoteProfitability;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public static bool $formActionsAreSticky = true;

    protected ?string $subheading = 'Actualizá el borrador antes de emitir el documento definitivo.';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($data['lines'] as $index => &$line) {
            $cost = $this->getRecord()->cost_snapshot['lines'][$index] ?? null;
            if ($cost && Gate::allows('view-costs')) {
                $line = QuoteProfitability::restoreInputs($line, $cost, $data['currency']);
            }
        }
        unset($line);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return array_values(array_filter(QuoteResource::documentActions(), fn ($action) => $action->getName() === 'deleteDraft'));
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(QuoteService::class)->saveDraft(auth()->user(), $data, $record);
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
