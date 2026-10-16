<?php

namespace App\Filament\Resources;

use App\Filament\Support\OperationAction;
use App\Models\User;
use App\Modules\Access\Models\AccessRole;
use App\Modules\Access\Services\AccessService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-user-group';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('users.manage') ?? false;
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cuenta individual')->description('Definí los datos, el rol y la contraseña del nuevo usuario.')->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(200), TextInput::make('email')->label('Correo')->email()->required()->maxLength(200),
                Select::make('role')->label('Rol')->options(fn () => AccessRole::pluck('name', 'key'))->required(),
                Select::make('status')->label('Estado')->options(['active' => 'Activo', 'suspended' => 'Suspendido', 'deleted' => 'Baja lógica'])->default('active')->required(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Confirmación y trazabilidad')->schema([
                TextInput::make('new_password')->label('Contraseña del nuevo usuario')->password()->autocomplete('new-password')->required()->minLength(12)->maxLength(128)->confirmed()->rule(Password::min(12)->letters()->numbers()->symbols())->helperText('Mínimo 12 caracteres, con letras, números y símbolos.')->visible(fn (string $operation) => $operation === 'create')->dehydrated(fn (string $operation) => $operation === 'create'),
                TextInput::make('new_password_confirmation')->label('Confirmar contraseña')->password()->autocomplete('new-password')->required()->visible(fn (string $operation) => $operation === 'create')->dehydrated(fn (string $operation) => $operation === 'create'),
                Textarea::make('reason')->label('Motivo del alta o cambio')->required()->minLength(10)->maxLength(1000), self::passwordField()->visible(fn (string $operation) => $operation !== 'create')->dehydrated(fn (string $operation) => $operation !== 'create'),
            ])->columnSpanFull(),
        ]);
    }

    public static function passwordField(): TextInput
    {
        return TextInput::make('current_password')->label('Tu contraseña actual')->password()->autocomplete('current-password')->required();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nombre')->searchable(), TextColumn::make('email')->label('Correo')->searchable(), TextColumn::make('role')->label('Rol')->formatStateUsing(fn ($state) => AccessRole::where('key', $state)->value('name') ?? $state), TextColumn::make('status')->label('Estado')->getStateUsing(fn (User $record) => $record->trashed() ? 'Baja' : ($record->is_active ? 'Activo' : 'Suspendido')), TextColumn::make('mfa')->label('Segundo factor')->getStateUsing(fn (User $record) => $record->app_authentication_secret ? 'Configurado' : 'Pendiente')])->recordActions([
            EditAction::make()->label('Editar')->color('gray'),
            Action::make('invite')->label('Enviar invitación / recuperación')->schema([self::passwordField()])->requiresConfirmation()->action(fn (User $record, array $data) => OperationAction::run(fn () => app(AccessService::class)->invite(auth()->user(), $record, $data['current_password']))),
            Action::make('revoke')->label('Cerrar sus sesiones')->schema([self::passwordField()])->requiresConfirmation()->action(fn (User $record, array $data) => OperationAction::run(fn () => app(AccessService::class)->revokeSessions(auth()->user(), $record, $data['current_password']))),
        ])->stackedOnMobile()->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\ListUsers::route('/'), 'create' => UserResource\Pages\CreateUser::route('/create'), 'edit' => UserResource\Pages\EditUser::route('/{record}/edit')];
    }
}
