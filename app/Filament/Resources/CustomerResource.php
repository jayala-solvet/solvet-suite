<?php

namespace App\Filament\Resources;

use App\Modules\Customers\Models\Customer;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

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
        return $schema->components([
            TextInput::make('name')->label('Razón social / nombre')->required()->maxLength(200), TextInput::make('tax_id')->label('RUC / identificación')->maxLength(50), TextInput::make('contact')->label('Contacto')->maxLength(200), TextInput::make('email')->label('Correo')->email()->maxLength(200), TextInput::make('phone')->label('Teléfono')->maxLength(50), Textarea::make('address')->label('Dirección')->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Cliente')->searchable()->sortable(), TextColumn::make('tax_id')->label('RUC')->searchable(), TextColumn::make('contact')->label('Contacto'), TextColumn::make('email')->label('Correo'), TextColumn::make('phone')->label('Teléfono'),
        ])->recordActions([EditAction::make()->visible(fn () => self::canCreate())])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => CustomerResource\Pages\ListCustomers::route('/'), 'create' => CustomerResource\Pages\CreateCustomer::route('/create'), 'edit' => CustomerResource\Pages\EditCustomer::route('/{record}/edit')];
    }
}
