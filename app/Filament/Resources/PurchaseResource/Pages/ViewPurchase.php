<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Filament\Support\OperationAction;
use App\Modules\Operations\Services\PurchaseService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected string $view = 'filament.operations.purchase';

    public function getTitle(): string
    {
        return $this->getRecord()->referenceLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')->label('Anular compra')->color('gray')->visible(fn () => in_array($this->getRecord()->status, ['draft', 'ordered'], true))->requiresConfirmation()->schema([Textarea::make('reason')->label('Motivo')->required()->minLength(10)->maxLength(1000)])->action(function (array $data) {
                OperationAction::run(fn () => app(PurchaseService::class)->cancel(auth()->user(), $this->getRecord(), $data['reason']));
                $this->redirect(PurchaseResource::getUrl('view', ['record' => $this->getRecord()]));
            }),
            Action::make('edit')->label('Editar borrador')->color('gray')->visible(fn () => $this->getRecord()->status === 'draft')->url(fn () => PurchaseResource::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('confirm')->label('Confirmar compra')->requiresConfirmation()->visible(fn () => $this->getRecord()->status === 'draft')->action(function () {
                OperationAction::run(fn () => app(PurchaseService::class)->confirm(auth()->user(), $this->getRecord()));
                $this->redirect(PurchaseResource::getUrl('view', ['record' => $this->getRecord()]));
            }),
            Action::make('receive')->label('Confirmar recepción')->requiresConfirmation()->modalDescription('Confirma todas las cantidades de esta compra. Ingresa los bienes con stock y registra los servicios vinculados al pedido. No genera factura fiscal.')->visible(fn () => $this->getRecord()->status === 'ordered')->action(function () {
                OperationAction::run(fn () => app(PurchaseService::class)->receive(auth()->user(), $this->getRecord()));
                $this->redirect(PurchaseResource::getUrl('view', ['record' => $this->getRecord()]));
            }),
        ];
    }
}
