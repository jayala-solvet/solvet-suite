<?php

namespace App\Filament\Resources;

use App\Modules\Core\Models\AuditEvent;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SecurityAuditResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-finger-print';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('audit.view') ?? false;
    }

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

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(), TextColumn::make('actor.name')->label('Actor')->placeholder('Sin sesión'), TextColumn::make('action')->label('Acción')->searchable(), TextColumn::make('metadata.result')->label('Resultado')->placeholder('Registro anterior'), TextColumn::make('subject_type')->label('Recurso'), TextColumn::make('subject_id')->label('ID')])->stackedOnMobile()->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SecurityAuditResource\Pages\ListSecurityAudits::route('/')];
    }
}
