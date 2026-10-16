<?php

namespace App\Filament\Resources;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Operations\Models\OrderLine;
use App\Modules\Operations\Models\Purchase;
use App\Modules\Quotes\Models\Quote;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $modelLabel = 'compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-shopping-cart';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Gate::allows('view-costs');
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function canCreate(): bool
    {
        return Gate::allows('manage-costs');
    }

    public static function canEdit($record): bool
    {
        return self::canCreate() && $record->status === 'draft';
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Proveedor y documento')->description('Registro interno de compra. Confirmar no modifica stock; la recepción confirmada sí.')->schema([
                TextInput::make('supplier_name')->label('Proveedor')->required()->maxLength(200), TextInput::make('supplier_tax_id')->label('RUC del proveedor')->required()->maxLength(30),
                TextInput::make('reference')->label('Referencia de cotización / factura')->required()->maxLength(200), DatePicker::make('purchase_date')->label('Fecha')->native()->default(today())->required(),
                Select::make('currency')->label('Moneda')->options(['PYG' => 'Guaraníes (PYG)', 'USD' => 'Dólares (USD)'])->default('PYG')->required()->live(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Bienes y servicios comprados')->schema([
                Repeater::make('lines')->label('Detalle')->minItems(1)->maxItems(100)->defaultItems(1)->reorderable(false)->schema([
                    Select::make('catalog_item_id')->label('Ítem comprable')->options(fn (Get $get) => CatalogItem::where('can_buy', true)->where('is_active', true)->where('currency', $get('../../currency') ?? 'PYG')->orderBy('name')->pluck('name', 'id'))->searchable()->required()->live()->columnSpanFull(),
                    View::make('filament.operations.purchase-item-unit')->viewData(fn (Get $get) => ['item' => CatalogItem::find($get('catalog_item_id'))])->columnSpanFull(),
                    TextInput::make('quantity')->label('Cantidad')->numeric()->default('1')->minValue('0.001')->required(), TextInput::make('unit_cost')->label('Costo unitario con IVA')->numeric()->minValue(0)->required(),
                    Select::make('tax_rate')->label('IVA incluido')->options(['0' => 'Sin IVA', '5' => '5%', '10' => '10%'])->default('10')->required(),
                    Select::make('vat_treatment')->label('IVA de compra')->options(['recoverable' => 'Recuperable', 'non_recoverable' => 'No recuperable'])->default('recoverable')->required(),
                    TextInput::make('extra_cost')->label('Otros costos totales de esta línea')->helperText('Neto de IVA recuperable; no incluye el envío de abajo.')->default('0')->numeric()->minValue(0),
                    TextInput::make('freight_share')->label('Envío asignado con IVA (reparto manual)')->default('0')->numeric()->minValue(0),
                    Select::make('order_line_id')->label('Intervención del pedido (opcional)')->options(fn (Get $get) => OrderLine::with('order')->where('catalog_item_id', $get('catalog_item_id'))->whereHas('order', fn ($q) => $q->where('status', 'confirmed')->where('currency', $get('../../currency') ?? 'PYG'))->get()->mapWithKeys(fn ($l) => [$l->id => $l->order->reference().' · '.$l->description.' · pendiente '.bcsub($l->quantity, $l->done_quantity, 3)]))->searchable()->columnSpanFull()->helperText('Servicios y bienes sin stock se imputan al pedido al recibir. Bienes con stock se costean al entregar.'),
                ])->columns(2)->addActionLabel('Agregar ítem'),
            ])->columnSpanFull(),
            Section::make('Envío de la compra')->description('Se distribuye entre bienes. El costo de configuración se registra como un servicio del pedido.')->schema([
                TextInput::make('freight_amount')->label('Envío total con IVA')->numeric()->default('0')->required()->minValue(0),
                Select::make('freight_tax_rate')->label('IVA del envío')->options(['0' => 'Sin IVA', '5' => '5%', '10' => '10%'])->default('10')->required(),
                Select::make('freight_vat_treatment')->label('IVA recuperable del envío')->options(['recoverable' => 'Recuperable', 'non_recoverable' => 'No recuperable'])->default('recoverable')->required(),
                Select::make('freight_method')->label('Distribuir envío por')->options(['quantity' => 'Cantidad de bienes', 'value' => 'Valor neto de bienes', 'manual' => 'Asignación manual'])->default('quantity')->required(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('Compra')->formatStateUsing(fn (Purchase $record) => $record->referenceLabel())->sortable(),
            TextColumn::make('supplier_name')->label('Proveedor')->description(fn (Purchase $record) => $record->reference)->searchable()->wrap(),
            TextColumn::make('purchase_date')->label('Fecha')->date('d/m/Y')->sortable(),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn ($state) => ['draft' => 'Borrador', 'ordered' => 'Confirmada', 'received' => 'Recibida', 'cancelled' => 'Anulada'][$state] ?? $state),
            TextColumn::make('total_minor')->label('Total informado')->formatStateUsing(fn (Purchase $record) => Quote::money($record->total_minor, $record->currency))->alignEnd(),
        ])->recordUrl(fn (Purchase $record) => self::getUrl('view', ['record' => $record]))->recordActions([Action::make('view')->label('Ver compra')->url(fn (Purchase $record) => self::getUrl('view', ['record' => $record]))])->stackedOnMobile()->defaultSort('id', 'desc')->emptyStateHeading('Registrá tu primera compra');
    }

    public static function getPages(): array
    {
        return ['index' => PurchaseResource\Pages\ListPurchases::route('/'), 'create' => PurchaseResource\Pages\CreatePurchase::route('/create'), 'edit' => PurchaseResource\Pages\EditPurchase::route('/{record}/edit'), 'view' => PurchaseResource\Pages\ViewPurchase::route('/{record}')];
    }
}
