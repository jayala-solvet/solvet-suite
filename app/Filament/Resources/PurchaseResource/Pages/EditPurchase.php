<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Modules\Operations\Services\PurchaseService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    public static bool $formActionsAreSticky = true;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $factor = $data['currency'] === 'USD' ? '100' : '1';
        $scale = $data['currency'] === 'USD' ? 2 : 0;
        $data['freight_amount'] = bcdiv($data['freight_minor'], $factor, $scale);
        foreach ($data['lines'] as &$line) {
            $line['unit_cost'] = bcdiv($line['unit_cost_minor'], $factor, $scale);
            $line['extra_cost'] = bcdiv($line['extra_minor'], $factor, $scale);
            $line['freight_share'] = bcdiv($line['freight_share_minor'], $factor, $scale);
        }unset($line);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PurchaseService::class)->save(auth()->user(), $data, $record);
    }

    protected function getRedirectUrl(): string
    {
        return PurchaseResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
