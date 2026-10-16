<?php

namespace App\Filament\Resources;

use App\Filament\Support\OperationAction;
use App\Modules\Legal\Models\LegalDocument;
use App\Modules\Legal\Services\LegalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalDocumentResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = LegalDocument::class;

    protected static ?string $modelLabel = 'versión legal';

    protected static ?string $pluralModelLabel = 'Versiones legales';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-document-text';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('legal.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
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
            Section::make('Nueva versión')->description('Los textos iniciales son borradores. Confirmá responsable, finalidades, proveedores, conservación y revisión jurídica antes de publicar.')->schema([
                Select::make('kind')->label('Documento')->options(LegalService::KINDS)->required()->live()->afterStateUpdated(fn ($state, Set $set) => $set('body', LegalService::template($state))),
                TextInput::make('purpose_key')->visible(fn (Get $get) => $get('kind') === 'optional')->label('Clave de finalidad opcional')->helperText('Una clave distinta por finalidad; mantenerla entre versiones.')->default('optional')->maxLength(80),
                TextInput::make('title')->label('Título y finalidad')->required()->maxLength(200), TextInput::make('version')->label('Versión')->required()->maxLength(50),
                Textarea::make('body')->label('Texto íntegro')->rows(20)->required()->minLength(100)->maxLength(30000)->columnSpanFull(), UserResource::passwordField(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Documento')->wrap(), TextColumn::make('version')->label('Versión'), TextColumn::make('published_at')->label('Publicación')->dateTime('d/m/Y H:i')->placeholder('Borrador no público'),
        ])->recordActions([
            Action::make('inspect')->label('Leer versión')->modalContent(fn (LegalDocument $record) => view('filament.legal.text', ['document' => $record]))->modalSubmitAction(false),
            Action::make('publish')->label('Publicar versión')->visible(fn (LegalDocument $record) => ! $record->published_at)->schema([
                Textarea::make('review_reference')->label('Referencia de revisión jurídica y confirmación de datos')->required()->minLength(10)->maxLength(1000),
                Checkbox::make('reviewed')->label('Confirmo que el texto refleja la operación real y fue validado jurídicamente')->accepted()->required(), UserResource::passwordField(),
            ])->requiresConfirmation()->action(fn (LegalDocument $record, array $data) => OperationAction::run(fn () => app(LegalService::class)->publish(auth()->user(), $record, $data['review_reference'], (bool) $data['reviewed'], $data['current_password']))),
        ])->stackedOnMobile()->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => LegalDocumentResource\Pages\ListLegalDocuments::route('/'), 'create' => LegalDocumentResource\Pages\CreateLegalDocument::route('/create')];
    }
}
