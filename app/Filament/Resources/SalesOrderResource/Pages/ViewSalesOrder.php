<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Filament\Resources\SalesOrderResource;
use App\Filament\Support\OperationAction;
use App\Models\User;
use App\Modules\Operations\Models\ExecutionEntry;
use App\Modules\Operations\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected string $view = 'filament.operations.order';

    public function getTitle(): string
    {
        return $this->getRecord()->reference();
    }

    public function result(): array
    {
        return app(OrderService::class)->result(auth()->user(), $this->getRecord());
    }

    public function entries()
    {
        Gate::authorize('view-costs');

        return ExecutionEntry::with('actor')->whereIn('order_line_id', $this->getRecord()->lines()->pluck('id'))->latest('id')->get();
    }

    private function refreshPage(): void
    {
        $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->getRecord()]));
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('assign')->label('Asignar técnico')->visible(fn () => Gate::allows('orders.update'))->schema([
                Select::make('assigned_to')->label('Técnico')->options(fn () => User::where('is_active', true)->get()->filter(fn ($u) => $u->allows('orders.view-own'))->pluck('name', 'id'))->placeholder('Sin asignar'),
            ])->action(function (array $data) {
                OperationAction::run(fn () => app(OrderService::class)->assign(auth()->user(), $this->getRecord(), $data['assigned_to'] ? (int) $data['assigned_to'] : null));
                $this->refreshPage();
            }),
            Action::make('reserve')->label('Reservar disponibles')->color('gray')->visible(fn () => Gate::allows('orders.update') && $this->getRecord()->status === 'confirmed')->requiresConfirmation()->action(function () {
                OperationAction::run(fn () => app(OrderService::class)->reserve(auth()->user(), $this->getRecord()));
                $this->refreshPage();
            }),
            Action::make('release')->label('Liberar reservas')->color('gray')->visible(fn () => Gate::allows('orders.update') && $this->getRecord()->status === 'confirmed')->requiresConfirmation()->action(function () {
                OperationAction::run(fn () => app(OrderService::class)->release(auth()->user(), $this->getRecord()));
                $this->refreshPage();
            }),
            Action::make('deliver')->label('Confirmar entrega')->visible(fn () => Gate::allows('orders.update') && $this->getRecord()->status === 'confirmed')->requiresConfirmation()->modalDescription('Entrega las cantidades actualmente reservadas, guarda su costo promedio aplicado y libera esas reservas.')->schema([Hidden::make('token')->default(fn () => Str::uuid()->toString())])->action(function (array $data) {
                OperationAction::run(fn () => app(OrderService::class)->deliver(auth()->user(), $this->getRecord(), $data['token']));
                $this->refreshPage();
            }),
            Action::make('own')->label('Registrar trabajo propio')->color('gray')->visible(fn () => Gate::allows('orders.update') && Gate::allows('view-costs') && $this->getRecord()->status === 'confirmed')->schema([
                Select::make('line_id')->label('Intervención')->options(fn () => $this->getRecord()->lines()->where('kind', 'service')->where(fn ($q) => $q->whereNull('source')->orWhereIn('source', ['own', 'manual']))->get()->mapWithKeys(fn ($l) => [$l->id => $l->description.' · pendiente '.bcsub($l->quantity, $l->done_quantity, 3).' '.$l->unit]))->required(),
                TextInput::make('quantity')->label('Cantidad / horas realizadas')->numeric()->minValue('0.001')->required(),
                TextInput::make('unit_cost')->label('Costo interno efectivo por hora / unidad')->helperText('Sin IVA crédito. Se usa una sola intervención propia o de tercero.')->numeric()->minValue(0)->required(),
                Textarea::make('reference')->label('Detalle del trabajo y referencia')->minLength(10)->maxLength(1000)->required(), Hidden::make('token')->default(fn () => Str::uuid()->toString()),
            ])->action(function (array $data) {
                $line = $this->getRecord()->lines()->findOrFail($data['line_id']);
                OperationAction::run(fn () => app(OrderService::class)->recordOwn(auth()->user(), $line, (string) $data['quantity'], (string) $data['unit_cost'], $data['reference'], $data['token']));
                $this->refreshPage();
            }),
            Action::make('executor')->label('Definir ejecutor del servicio')->color('gray')->visible(fn () => Gate::allows('orders.update') && $this->getRecord()->status === 'confirmed')->schema([
                Select::make('line_id')->label('Servicio')->options(fn () => $this->getRecord()->lines()->where('kind', 'service')->where('done_quantity', 0)->pluck('description', 'id'))->required(),
                Select::make('source')->label('Ejecutor real')->options(['own' => 'Solvet-IT / trabajo propio', 'external' => 'Proveedor externo con compra'])->required(),
                Textarea::make('reason')->label('Motivo del cambio')->minLength(10)->maxLength(1000)->required(),
            ])->requiresConfirmation()->modalDescription('Conserva los costos estimados originales. Solo cambia quién ejecutará el servicio pendiente; revisá las compras vinculadas antes de continuar.')->action(function (array $data) {
                OperationAction::run(fn () => app(OrderService::class)->chooseExecutor(auth()->user(), $this->getRecord()->lines()->findOrFail($data['line_id']), $data['source'], $data['reason']));
                $this->refreshPage();
            }),
            Action::make('purchase')->label('Comprar a proveedor')->color('gray')->visible(fn () => Gate::allows('purchases.create') && $this->getRecord()->status === 'confirmed')->url(PurchaseResource::getUrl('create')),
            Action::make('void')->label('Anular ejecución')->color('gray')->visible(fn () => Gate::allows('orders.update') && Gate::allows('view-costs'))->schema([
                Select::make('entry_id')->label('Registro de ejecución')->options(fn () => $this->entries()->whereNull('voided_at')->mapWithKeys(fn ($e) => [$e->id => '#'.$e->id.' · '.$e->quantity.' · '.$e->reference]))->required(), Textarea::make('reason')->label('Motivo')->minLength(10)->maxLength(1000)->required(),
            ])->requiresConfirmation()->modalDescription('Conserva el registro, revierte la cantidad realizada y reabre el pedido. No anula la compra ni genera un documento fiscal.')->action(function (array $data) {
                $entry = $this->entries()->firstWhere('id', $data['entry_id']);
                abort_unless($entry, 404);
                OperationAction::run(fn () => app(OrderService::class)->voidExecution(auth()->user(), $entry, $data['reason']));
                $this->refreshPage();
            }),
            Action::make('cancel')->label('Anular pedido')->color('gray')->visible(fn () => Gate::allows('orders.approve') && $this->getRecord()->status === 'confirmed')->requiresConfirmation()->schema([Textarea::make('reason')->label('Motivo')->required()->minLength(10)->maxLength(1000)])->action(function (array $data) {
                OperationAction::run(fn () => app(OrderService::class)->cancel(auth()->user(), $this->getRecord(), $data['reason']));
                $this->refreshPage();
            }),
            Action::make('complete')->label('Cerrar ejecución')->color('gray')->requiresConfirmation()->visible(fn () => Gate::allows('orders.approve') && $this->getRecord()->status === 'confirmed')->action(function () {
                OperationAction::run(fn () => app(OrderService::class)->complete(auth()->user(), $this->getRecord()));
                $this->refreshPage();
            }),
        ];

        return [collect($actions)->first(fn ($a) => $a->getName() === 'reserve'), collect($actions)->first(fn ($a) => $a->getName() === 'deliver'), ActionGroup::make(array_values(array_filter($actions, fn ($a) => ! in_array($a->getName(), ['reserve', 'deliver'], true))))->label('Más acciones')->button()->color('gray')];
    }
}
