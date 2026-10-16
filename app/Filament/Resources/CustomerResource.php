<?php

namespace App\Filament\Resources;

use App\Modules\Customers\Models\Customer;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 2;

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
        return $schema->components(self::fields());
    }

    public static function fields(): array
    {
        return [
            Section::make('Identificación del cliente')->description('Datos que se utilizarán al preparar tus presupuestos.')->icon('heroicon-o-users')->schema([
                TextInput::make('name')->label('Razón social / nombre')->required()->maxLength(200),
                TextInput::make('tax_id')->label('RUC / identificación')->maxLength(50),
            ])->columns(2)->columnSpanFull(),
            Section::make('Contacto y ubicación')->description('Información para coordinar la propuesta y el servicio.')->icon('heroicon-o-user-circle')->schema([
                TextInput::make('contact')->label('Contacto')->maxLength(200),
                TextInput::make('email')->label('Correo')->email()->maxLength(200),
                TextInput::make('phone')->label('Teléfono')->maxLength(50),
                Textarea::make('address')->label('Dirección')->maxLength(1000)->rows(3)->columnSpanFull(),
            ])->columns(['default' => 1, 'md' => 3])->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Cliente')->searchable()->sortable()->weight(FontWeight::SemiBold)->wrap(), TextColumn::make('tax_id')->label('RUC')->searchable()->placeholder('Sin registrar'), TextColumn::make('contact')->label('Contacto')->placeholder('Sin registrar'), TextColumn::make('email')->label('Correo')->placeholder('Sin registrar'), TextColumn::make('phone')->label('Teléfono')->placeholder('Sin registrar'),
        ])->recordActions([EditAction::make()->color('gray')->visible(fn () => self::canCreate())])->recordActionsColumnLabel('Opciones')->stackedOnMobile()->searchPlaceholder('Buscar por nombre o RUC')->emptyStateIcon('heroicon-m-user-group')->emptyStateHeading('No hay clientes para mostrar')->emptyStateDescription('Revisá la búsqueda. Los clientes registrados aparecerán en este listado.')->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => CustomerResource\Pages\ListCustomers::route('/'), 'create' => CustomerResource\Pages\CreateCustomer::route('/create'), 'edit' => CustomerResource\Pages\EditCustomer::route('/{record}/edit')];
    }
}
