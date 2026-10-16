<?php

namespace App\Filament\Resources;

use App\Filament\Support\OperationAction;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Operations\Services\InventoryService;
use App\Modules\Operations\Services\OperationRules;
use App\Modules\Quotes\Models\Quote;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InventoryResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static ?string $slug = 'inventory';

    protected static ?string $modelLabel = 'existencia';

    protected static ?string $pluralModelLabel = 'Inventario';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-cube';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'product')->where('track_stock', true)->with('stock');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sku')->label('SKU')->searchable(), TextColumn::make('name')->label('Bien')->searchable()->wrap(),
            TextColumn::make('physical')->label('Físico')->getStateUsing(fn (CatalogItem $r) => $r->stock?->quantity ?? '0.000')->formatStateUsing(fn ($state) => OperationRules::quantityLabel($state))->alignEnd(),
            TextColumn::make('reserved')->label('Reservado')->getStateUsing(fn (CatalogItem $r) => $r->stock?->reserved ?? '0.000')->formatStateUsing(fn ($state) => OperationRules::quantityLabel($state))->alignEnd(),
            TextColumn::make('available')->label('Disponible')->getStateUsing(fn (CatalogItem $r) => $r->stock?->available() ?? '0.000')->formatStateUsing(fn ($state) => OperationRules::quantityLabel($state))->alignEnd(),
            TextColumn::make('average')->label('Costo promedio')->getStateUsing(fn (CatalogItem $r) => $r->stock && bccomp($r->stock->quantity, '0', 3) > 0 ? Quote::money(CostCalculator::round($r->stock->average()), $r->currency) : 'Sin existencias')->visible(fn () => Gate::allows('view-costs'))->alignEnd(),
        ])->recordUrl(fn (CatalogItem $record) => self::getUrl('view', ['record' => $record]))->recordActions([
            Action::make('adjust')->label('Ajustar saldo')->icon('heroicon-o-adjustments-horizontal')->color('gray')->visible(fn () => Gate::allows('manage-costs'))->schema([
                Select::make('direction')->label('Movimiento')->options(['in' => 'Entrada / saldo inicial', 'out' => 'Salida por ajuste'])->default('in')->required(),
                TextInput::make('quantity')->label('Cantidad')->numeric()->minValue('0.001')->required(),
                TextInput::make('unit_cost')->label('Costo efectivo unitario de entrada')->helperText('Neto de IVA recuperable; admite hasta 8 decimales. En una salida se usa el promedio actual.')->numeric()->minValue(0),
                Textarea::make('reason')->label('Motivo y documento de respaldo')->required()->minLength(10)->maxLength(1000), Hidden::make('token')->default(fn () => Str::uuid()->toString()),
            ])->requiresConfirmation()->action(function (CatalogItem $record, array $data) {
                OperationAction::run(fn () => app(InventoryService::class)->adjust(auth()->user(), $record, $data['direction'], (string) $data['quantity'], isset($data['unit_cost']) ? (string) $data['unit_cost'] : null, $data['reason'], $data['token']));
            }),
        ])->stackedOnMobile()->emptyStateHeading('Sin bienes con control de stock')->emptyStateDescription('Activá Controlar stock en el catálogo. El saldo inicial requiere un ajuste documentado.');
    }

    public static function getPages(): array
    {
        return ['index' => InventoryResource\Pages\ListInventory::route('/'), 'view' => InventoryResource\Pages\ViewInventory::route('/{record}')];
    }
}
