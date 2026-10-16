<?php

namespace App\Filament\Resources;

use App\Filament\Support\OperationAction;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Models\CostProfile;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Costs\Services\CostService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CostProfileResource extends Resource
{
    protected static ?string $model = CostProfile::class;

    protected static ?string $modelLabel = 'versión de costo';

    protected static ?string $pluralModelLabel = 'Costos y precios';

    protected static ?string $navigationLabel = 'Costos y precios';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-calculator';

    protected static ?int $navigationSort = 4;

    protected static bool $hasTitleCaseModelLabel = false;

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
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Producto o servicio')->description('Cada registro crea una versión. Las anteriores se conservan para la trazabilidad de los presupuestos.')->schema([
                Select::make('catalog_item_id')->label('Ítem del catálogo')->relationship('item', 'name')->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $item = CatalogItem::find($state);
                        if ($item?->kind === 'product') {
                            $set('source', 'purchase');
                            $set('purchase_tax_rate', '10');
                            $set('vat_treatment', 'recoverable');
                        } else {
                            $set('source', 'own');
                            $set('purchase_tax_rate', '0');
                            $set('vat_treatment', 'non_recoverable');
                        }
                    })->helperText('Creá primero el producto o las horas N1/N2 en Catálogo. El costo usa su moneda y unidad.'),
                Select::make('source')->label('Origen del costo')->options(fn (Get $get) => CatalogItem::find($get('catalog_item_id'))?->kind === 'product' ? ['purchase' => CostProfile::SOURCES['purchase']] : ['own' => CostProfile::SOURCES['own'], 'external' => CostProfile::SOURCES['external']])->required()->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('purchase_tax_rate', $state === 'own' ? '0' : '10');
                        $set('vat_treatment', $state === 'own' ? 'non_recoverable' : 'recoverable');
                    }),
                TextInput::make('supplier_name')->label('Proveedor / técnico externo')->maxLength(200)->visible(fn (Get $get) => in_array($get('source'), ['purchase', 'external'], true))->required(fn (Get $get) => $get('source') !== 'own'),
                TextInput::make('supplier_tax_id')->label('RUC del proveedor')->maxLength(30)->visible(fn (Get $get) => in_array($get('source'), ['purchase', 'external'], true)),
                TextInput::make('reference')->label('Referencia de cotización o factura')->maxLength(200),
                DatePicker::make('cost_date')->label('Fecha del costo')->native()->default(today())->required(),
                DatePicker::make('valid_until')->label('Vigencia del costo (opcional)')->native()->minDate(today())->helperText('Al vencer se advierte en el presupuesto; no se elimina el costo.'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Costo unitario y precio sugerido')->description('Compras y terceros: importe con IVA incluido. Trabajo propio: costo interno por unidad/hora, sin IVA crédito.')->schema([
                TextInput::make('unit_cost')->label('Costo unitario informado')->required()->numeric()->minValue(0)->live(debounce: 500)
                    ->rules(fn (Get $get) => ['regex:'.QuoteCalculator::pricePattern(CatalogItem::find($get('catalog_item_id'))?->currency ?? 'PYG')]),
                Select::make('purchase_tax_rate')->label('IVA incluido en el costo')->options(['0' => 'Sin IVA', '5' => '5%', '10' => '10%'])->required()->live(),
                Select::make('vat_treatment')->label('Tratamiento del IVA de compra')->options(CostProfile::VAT)->required()->live()
                    ->helperText('Recuperable previsto supone factura válida a tu RUC y derecho al crédito fiscal. No registra una declaración de IVA.'),
                TextInput::make('extra_cost')->label('Gastos adicionales por unidad (IVA incluido)')->numeric()->minValue(0)->default('0')->required()->live(debounce: 500)
                    ->rules(fn (Get $get) => ['regex:'.QuoteCalculator::pricePattern(CatalogItem::find($get('catalog_item_id'))?->currency ?? 'PYG')])
                    ->helperText('Flete, traslado u otros por unidad. Definí su IVA por separado; no dupliques el envío de una compra.'),
                Select::make('extra_tax_rate')->label('IVA incluido en gastos adicionales')->options(['0' => 'Sin IVA', '5' => '5%', '10' => '10%'])->default('10')->required()->live(),
                Select::make('extra_vat_treatment')->label('IVA de gastos adicionales')->options(['recoverable' => 'Recuperable', 'non_recoverable' => 'No recuperable'])->default('recoverable')->required()->live(),
                Select::make('pricing_mode')->label('Regla de precio')->options(CostProfile::MODES)->default('margin')->required()->live(),
                TextInput::make('pricing_value')->label(fn (Get $get) => match ($get('pricing_mode')) {
                    'fixed_profit' => 'Utilidad deseada por unidad','manual' => 'Precio final al cliente con IVA',default => 'Porcentaje deseado'
                })
                    ->numeric()->minValue(0)->required()->live(debounce: 500)->helperText('El margen se mide sobre venta sin IVA. Por ejemplo: 20% de margen equivale a 25% de recargo sobre costo.'),
                Hidden::make('apply_price')->default(false),
                View::make('filament.costs.calculation')->viewData(function (Get $get) {
                    $item = CatalogItem::find($get('catalog_item_id'));
                    $currency = $item?->currency ?? 'PYG';
                    try {
                        $calculation = $item ? app(CostCalculator::class)->calculate(collect(['source', 'unit_cost', 'extra_cost', 'extra_tax_rate', 'extra_vat_treatment', 'purchase_tax_rate', 'vat_treatment', 'pricing_mode', 'pricing_value'])->mapWithKeys(fn ($key) => [$key => $get($key)])->all(), $currency, (int) $item->tax_rate) : null;
                    } catch (ValidationException $e) {
                        $calculation = null;
                    }

                    return compact('calculation', 'currency', 'item');
                })->columnSpanFull(),
                Textarea::make('notes')->label('Observaciones internas')->maxLength(1000)->rows(3)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('Versión')->prefix('#')->sortable(),
            TextColumn::make('item.name')->label('Producto / servicio')->searchable()->wrap()
                ->description(fn (CostProfile $record) => $record->currency.' · '.($record->unit === 'hour' ? 'por hora' : 'por unidad')),
            TextColumn::make('source')->label('Origen')->formatStateUsing(fn ($state) => CostProfile::SOURCES[$state])->description(fn (CostProfile $record) => $record->supplier_name ?? 'Trabajo propio'),
            TextColumn::make('effective_unit_minor')->label('Costo efectivo')->placeholder('IVA pendiente')->formatStateUsing(fn (CostProfile $record) => Quote::money(CostCalculator::round($record->effective_unit_minor), $record->currency))->alignEnd(),
            TextColumn::make('suggested_price_minor')->label('Precio sugerido')->placeholder('Sin determinar')->formatStateUsing(fn (CostProfile $record) => Quote::money($record->suggested_price_minor, $record->currency))->alignEnd(),
            TextColumn::make('cost_date')->label('Fecha')->date('d/m/Y')->sortable(),
            TextColumn::make('valid_until')->label('Vigencia')->date('d/m/Y')->placeholder('Sin límite')->description(fn (CostProfile $record) => $record->expired() ? 'Costo vencido' : null),
            TextColumn::make('current')->label('Uso')->getStateUsing(fn (CostProfile $record) => (int) $record->item->current_cost_id === (int) $record->id ? 'Actual' : 'Histórico'),
        ])->modifyQueryUsing(fn ($query) => $query->with('item'))->filters([
            SelectFilter::make('catalog_item_id')->label('Ítem')->relationship('item', 'name')->searchable()->preload(),
            SelectFilter::make('source')->label('Origen')->options(CostProfile::SOURCES),
        ])->recordActions([
            Action::make('applyPrice')->visible(fn () => Gate::allows('costs.approve'))->label('Aplicar precio al catálogo')->icon('heroicon-o-check')->color('gray')->requiresConfirmation()->modalDescription('Publica este precio para futuras selecciones. No modifica presupuestos guardados ni revaloriza stock.')->action(fn (CostProfile $record) => OperationAction::run(fn () => app(CostService::class)->applyPrice(auth()->user(), $record))),
            Action::make('calculation')->label('Ver cálculo')->icon('heroicon-o-document-magnifying-glass')->color('gray')->modalHeading('Detalle de la versión de costo')
                ->modalContent(fn (CostProfile $record) => view('filament.costs.profile', ['profile' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Cerrar'),
        ])->stackedOnMobile()->defaultSort('id', 'desc')->emptyStateHeading('Definí el costo de tu primer producto o servicio')
            ->emptyStateDescription('Usá el catálogo existente. Cada actualización conserva una versión anterior.');
    }

    public static function getPages(): array
    {
        return ['index' => CostProfileResource\Pages\ListCostProfiles::route('/'), 'create' => CostProfileResource\Pages\CreateCostProfile::route('/create')];
    }
}
