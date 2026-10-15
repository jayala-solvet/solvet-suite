<?php

namespace App\Filament\Resources;

use App\Modules\Core\Models\Company;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $modelLabel = 'empresa';

    protected static ?string $pluralModelLabel = 'Mi empresa';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-building-office-2';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identidad de la empresa')->icon('heroicon-o-building-office')->description('Datos del emisor que aparecerán en los nuevos presupuestos.')->schema([
                TextInput::make('name')->label('Razón social')->required()->maxLength(200),
                TextInput::make('tax_id')->label('RUC')->maxLength(50),
            ])->columns(2)->columnSpanFull(),
            Section::make('Información de contacto')->icon('heroicon-o-envelope')->schema([
                TextInput::make('email')->label('Correo')->email()->maxLength(200),
                TextInput::make('phone')->label('Teléfono')->maxLength(50),
                Textarea::make('address')->label('Dirección')->maxLength(1000)->rows(3)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Empresa')->wrap(), TextColumn::make('tax_id')->label('RUC')->placeholder('Sin registrar'), TextColumn::make('email')->label('Correo')->placeholder('Sin registrar')])->recordActions([EditAction::make()->color('gray')])->recordActionsColumnLabel('Opciones')->stackedOnMobile();
    }

    public static function getPages(): array
    {
        return ['index' => CompanyResource\Pages\ListCompanies::route('/'), 'edit' => CompanyResource\Pages\EditCompany::route('/{record}/edit')];
    }
}
