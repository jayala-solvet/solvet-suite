<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Filament\Support\OperationAction;
use App\Modules\Operations\Models\StockMovement;
use App\Modules\Operations\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class ViewInventory extends ViewRecord
{
    use WithPagination;

    protected static string $resource = InventoryResource::class;

    protected string $view = 'filament.operations.inventory';

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function movements()
    {
        return StockMovement::with('actor')->where('catalog_item_id', $this->getRecord()->id)->latest('id')->paginate(25);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')->label('Registrar devolución')->color('gray')->visible(fn () => Gate::allows('inventory.update'))->schema([
                Select::make('movement_id')->label('Movimiento original')->options(fn () => StockMovement::with('actor')->where('catalog_item_id', $this->getRecord()->id)->whereIn('kind', ['receipt', 'delivery'])->whereRaw('ABS(quantity) > returned_quantity')->latest('id')->get()->mapWithKeys(fn ($m) => [$m->id => '#'.$m->id.' · '.($m->kind === 'receipt' ? 'Devolver al proveedor' : 'Devolución del cliente').' · '.$m->quantity.' · '.$m->created_at->format('d/m/Y')]))->required()->searchable(),
                TextInput::make('quantity')->label('Cantidad a devolver')->numeric()->minValue('0.001')->required(), Textarea::make('reason')->label('Motivo / referencia')->required()->minLength(10)->maxLength(1000), Hidden::make('token')->default(fn () => Str::uuid()->toString()),
            ])->requiresConfirmation()->modalDescription('La devolución queda vinculada al movimiento original. Una devolución del cliente reabre el pedido para resolver la entrega; no genera nota de crédito fiscal.')->action(function (array $data) {
                $original = StockMovement::with('actor')->where('catalog_item_id', $this->getRecord()->id)->findOrFail($data['movement_id']);
                OperationAction::run(fn () => app(InventoryService::class)->returnMovement(auth()->user(), $original, (string) $data['quantity'], $data['reason'], $data['token']));
                $this->redirect(InventoryResource::getUrl('view', ['record' => $this->getRecord()]));
            }),
        ];
    }
}
