<?php

namespace App\Filament\Resources;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteCalculator;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static ?string $modelLabel = 'producto o servicio';

    protected static ?string $pluralModelLabel = 'Productos y servicios';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-archive-box';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Catálogo';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canCreate(): bool
    {
        return auth()->user()?->canWrite() ?? false;
    }

    public static function canEdit($record): bool
    {
        return self::canCreate();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Producto o servicio')->description('Catálogo opcional. Los datos se copian al presupuesto y pueden ajustarse allí.')->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(200)->columnSpanFull(),
                TextInput::make('sku')->label('Código / SKU')->maxLength(50)->unique(ignoreRecord: true),
                Select::make('unit')->label('Unidad de cotización')->options(['unit' => 'Unidad', 'hour' => 'Hora'])->default('unit')->required()->helperText('Para soporte N1/N2 utilizá Hora. Con historial de costos, cambiar unidad, tipo o moneda requiere otro ítem. Cambiar IVA desvincula el costo actual.'),
                Select::make('kind')->label('Tipo')->options(['product' => 'Producto', 'service' => 'Servicio'])->default('service')->required(),
                Toggle::make('can_purchase')->label('Se puede comprar')->default(true),
                Toggle::make('can_sell')->label('Se puede vender')->default(true),
                Toggle::make('track_stock')->label('Controlar stock')->default(false)
                    ->helperText('Solo para bienes físicos. No crea stock ni movimientos hasta implementar Inventario.'),
                Textarea::make('description')->label('Descripción para el presupuesto')->helperText('Si se deja vacía, se usará el nombre.')->maxLength(1000)->rows(3)->columnSpanFull(),
                Select::make('currency')->label('Moneda')->options(['PYG' => 'Guaraníes (PYG)', 'USD' => 'Dólares (USD)'])->default('PYG')->required(),
                TextInput::make('unit_price')->label('Precio con IVA incluido')->numeric()->minValue(0)->required()
                    ->rules(fn (Get $get) => ['regex:'.QuoteCalculator::pricePattern($get('currency') ?? 'PYG')])
                    ->validationMessages(['regex' => 'Ingresá un precio válido: PYG entero o USD con hasta 2 decimales.'])->helperText('PYG sin decimales; USD hasta 2. No se convierte automáticamente al cambiar la moneda.'),
                Select::make('tax_rate')->label('IVA incluido')->options(['0' => 'Exento', '5' => '5%', '10' => '10%'])->default('10')->required(),
                Toggle::make('is_active')->label('Disponible para nuevos presupuestos')->default(true)->helperText('Desactivar no modifica propuestas guardadas.'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sku')->label('Código')->placeholder('Sin código')->searchable(),
            TextColumn::make('name')->label('Producto / servicio')->searchable()->sortable()->wrap(),
            TextColumn::make('kind')->label('Tipo')->formatStateUsing(fn ($state) => $state === 'product' ? 'Producto' : 'Servicio'),
            TextColumn::make('price_minor')->label('Precio')->formatStateUsing(fn (CatalogItem $record) => Quote::money($record->price_minor, $record->currency))->alignEnd(),
            TextColumn::make('tax_rate')->label('IVA incluido')->suffix('%'),
            IconColumn::make('is_active')->label('Disponible')->boolean(),
        ])->filters([
            SelectFilter::make('currency')->label('Moneda')->options(['PYG' => 'PYG', 'USD' => 'USD']),
            TernaryFilter::make('is_active')->label('Disponibilidad'),
        ])->recordActions([EditAction::make()->color('gray')])->stackedOnMobile()->defaultSort('name')
            ->emptyStateHeading('Tu catálogo puede empezar cuando lo necesites')
            ->emptyStateDescription('Podés cargar presupuestos manualmente aunque no registres productos o servicios.');
    }

    public static function getPages(): array
    {
        return ['index' => CatalogItemResource\Pages\ListCatalogItems::route('/'), 'create' => CatalogItemResource\Pages\CreateCatalogItem::route('/create'), 'edit' => CatalogItemResource\Pages\EditCatalogItem::route('/{record}/edit')];
    }
}
