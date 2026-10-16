<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public static bool $formActionsAreSticky = true;

    protected ?string $subheading = 'Actualizá el borrador antes de emitir el documento definitivo.';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($data['lines'] as $index => &$line) {
            $cost = $this->getRecord()->cost_snapshot['lines'][$index] ?? null;
            $line['cost_profile_id'] = $cost['profile_id'] ?? null;
            if (($cost['source'] ?? null) === 'manual' && isset($cost['unit_net_minor'])) {
                $line['internal_unit_cost'] = bcdiv($cost['unit_net_minor'], $data['currency'] === 'USD' ? '100' : '1', $data['currency'] === 'USD' ? 2 : 0);
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
