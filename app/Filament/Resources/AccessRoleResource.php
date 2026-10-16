<?php

namespace App\Filament\Resources;

use App\Modules\Access\Models\AccessRole;
use App\Modules\Access\Services\Permissions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessRoleResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = AccessRole::class;

    protected static ?string $modelLabel = 'rol';

    protected static ?string $pluralModelLabel = 'Roles y permisos';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-shield-check';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('roles.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny() && $record->key !== 'admin';
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')->schema([TextInput::make('key')->label('Clave')->required()->maxLength(50)->helperText('No se cambia después de crear el rol.'), TextInput::make('name')->label('Nombre')->required()->maxLength(100)])->columns(2)->columnSpanFull(),
            Section::make('Permisos explícitos')->description('Sin marcar significa denegado. Recursos humanos no recibe acceso a módulos financieros por defecto. Consultar compras incluye sus importes.')->schema([CheckboxList::make('permissions')->label('Módulos y acciones')->options(Permissions::all())->columns(2)->default([])])->columnSpanFull(),
            Section::make('Confirmación')->schema([Textarea::make('reason')->label('Justificación')->required()->minLength(10)->maxLength(1000), UserResource::passwordField()])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Rol'), TextColumn::make('key')->label('Clave'), TextColumn::make('permission_count')->label('Permisos otorgados')->getStateUsing(fn ($record) => count($record->permissions))])->recordActions([EditAction::make()->color('gray')])->stackedOnMobile();
    }

    public static function getPages(): array
    {
        return ['index' => AccessRoleResource\Pages\ListAccessRoles::route('/'), 'create' => AccessRoleResource\Pages\CreateAccessRole::route('/create'), 'edit' => AccessRoleResource\Pages\EditAccessRole::route('/{record}/edit')];
    }
}
