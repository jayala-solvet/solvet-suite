<?php

namespace App\Filament\Resources;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $modelLabel = 'presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->user()?->canWrite() ?? false;
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
            Section::make('Datos del presupuesto')->description('Definí el destinatario, la moneda y la vigencia de tu propuesta.')->icon('heroicon-o-document-text')->schema([
                Select::make('customer_id')->label('Cliente')->relationship('customer', 'name')->searchable()->preload()->required(),
                TextInput::make('subject')->label('Referencia / asunto')->required()->maxLength(200),
                Select::make('currency')->label('Moneda')->options(['PYG' => 'Guaraníes (PYG)', 'USD' => 'Dólares (USD)'])->default('PYG')->required(),
                DatePicker::make('valid_until')->native(false)->displayFormat('d/m/Y')->locale('es')->label('Válido hasta')->default(now()->addDays(15))->required(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Productos y servicios')->icon('heroicon-o-squares-plus')->description('Precios con IVA incluido. Los totales se calculan al guardar. PYG sin decimales; USD hasta 2.')->schema([
                Repeater::make('lines')->label('Detalle')->hiddenLabel()->itemLabel('Ítem')->itemNumbers()->schema([
                    Textarea::make('description')->label('Descripción')->required()->maxLength(1000)->columnSpan(['default' => 1, 'md' => 2, 'xl' => 4])->rows(2),
                    TextInput::make('quantity')->label('Cantidad')->numeric()->minValue('0.001')->maxValue(999999.999)->default('1')->required()->columnSpan(['xl' => 2]),
                    TextInput::make('unit_price')->label('Precio unitario')->numeric()->minValue(0)->maxValue(999999999)->required()->columnSpan(['xl' => 2]),
                    TextInput::make('discount')->label('Dto. %')->numeric()->minValue(0)->maxValue(100)->default('0')->required()->columnSpan(['xl' => 2]),
                    Select::make('tax_rate')->label('IVA incluido')->options(['0' => 'Exento', '5' => '5%', '10' => '10%'])->default('10')->required()->columnSpan(['xl' => 2]),
                ])->columns(['default' => 1, 'md' => 2, 'xl' => 12])->minItems(1)->maxItems(100)->defaultItems(1)->addActionLabel('Agregar ítem')->addAction(fn (Action $action) => $action->color('gray')->outlined()->icon('heroicon-o-plus'))->reorderable(false),
            ])->columnSpanFull(),
            Section::make('Condiciones comerciales')->description('Forma de pago, entrega y garantía que acompañarán al documento.')->icon('heroicon-o-clipboard-document-list')->schema([
                Textarea::make('terms')->label('Condiciones comerciales')->hiddenLabel()->rows(4)->maxLength(5000)->default("Forma de pago: a convenir.\nPlazo de entrega: a confirmar.\nGarantía: según producto o servicio."),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('number')->label('Documento / referencia')->getStateUsing(fn (Quote $record) => $record->reference())->description(fn (Quote $record) => Str::limit($record->subject, 64))->tooltip(fn (Quote $record) => $record->subject)->weight(FontWeight::SemiBold)->searchable(['number', 'subject'])->sortable()->extraAttributes(['class' => 'sv-document-column']),
            TextColumn::make('customer.name')->label('Cliente')->searchable()->sortable()->wrap(),
            TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($record) => $record->statusLabel())->color(fn (string $state) => match ($state) {
                'accepted' => 'success','issued' => 'info','rejected','cancelled' => 'danger',default => 'gray'
            }),
            TextColumn::make('total_minor')->label('Total')->formatStateUsing(fn ($record) => $record->formattedTotal())->alignEnd()->weight(FontWeight::SemiBold)->extraAttributes(['class' => 'sv-amount']),
            TextColumn::make('valid_until')->label('Vigencia')->date('d/m/Y')->sortable(),
        ])->filters([SelectFilter::make('status')->label('Estado')->options(['draft' => 'Borrador', 'issued' => 'Emitido', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'cancelled' => 'Anulado'])])->recordActions([
            Action::make('pdf')->label('PDF')->icon('heroicon-o-arrow-down-tray')->url(fn (Quote $record) => route('quotes.pdf', $record))->openUrlInNewTab(),
            ActionGroup::make([
                EditAction::make()->label('Editar')->visible(fn (Quote $record) => self::canEdit($record)),
                Action::make('issue')->label('Emitir')->icon('heroicon-o-paper-airplane')->requiresConfirmation()->modalDescription('Se asignará un correlativo y se conservará el PDF. El documento dejará de ser editable. Emitir no envía correos.')->visible(fn (Quote $record) => self::canCreate() && $record->status === 'draft')->action(fn (Quote $record) => app(QuoteService::class)->issue(auth()->user(), $record)),
                Action::make('decision')->label('Registrar decisión')->icon('heroicon-o-check-circle')->visible(fn (Quote $record) => self::canCreate() && $record->status === 'issued')->schema([
                    Select::make('status')->label('Resultado')->options(['accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'cancelled' => 'Anulado'])->required(),
                    Textarea::make('evidence')->label('Evidencia / referencia')->helperText('Indicá fecha, contacto y referencia del correo, ticket o aprobación.')->minLength(10)->maxLength(2000)->required(),
                ])->action(fn (Quote $record, array $data) => app(QuoteService::class)->decide(auth()->user(), $record, $data['status'], $data['evidence'])),
                Action::make('history')->label('Historial')->url(fn (Quote $record) => route('quotes.history', $record))->openUrlInNewTab(),
            ])->label('Acciones')->icon('heroicon-o-ellipsis-horizontal')->color('gray')->button()->outlined(),
        ])->recordActionsColumnLabel('Opciones')->stackedOnMobile()->searchPlaceholder('Buscar documento o cliente')->defaultSort('id', 'desc')->emptyStateIcon('heroicon-o-document-plus')->emptyStateHeading('No hay presupuestos para mostrar')->emptyStateDescription('Revisá la búsqueda y los filtros. Los presupuestos registrados aparecerán en este listado.');
    }

    public static function getPages(): array
    {
        return ['index' => QuoteResource\Pages\ListQuotes::route('/'), 'create' => QuoteResource\Pages\CreateQuote::route('/create'), 'edit' => QuoteResource\Pages\EditQuote::route('/{record}/edit')];
    }
}
