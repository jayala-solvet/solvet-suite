<?php

namespace App\Filament\Resources;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Services\CustomerService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteCalculator;
use App\Modules\Quotes\Services\QuoteDefaults;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $modelLabel = 'presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-document-text';

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
        return self::canEdit($record) && $record->number === null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del presupuesto')->description('Definí el destinatario, la moneda y la vigencia de tu propuesta.')->icon('heroicon-o-document-text')->schema([
                Select::make('customer_id')->label('Cliente')->relationship('customer', 'name')->searchable()->preload()->required()
                    ->createOptionForm(CustomerResource::fields())
                    ->createOptionAction(fn (Action $action) => $action->modalHeading('Nuevo cliente')->modalSubmitActionLabel('Crear y seleccionar'))
                    ->createOptionUsing(fn (array $data) => app(CustomerService::class)->create(auth()->user(), $data)->getKey()),
                TextInput::make('subject')->label('Referencia / asunto')->required()->maxLength(200),
                Select::make('currency')->live()->helperText('Cambiar la moneda no convierte los precios. Revisá los ítems.')->label('Moneda')->options(['PYG' => 'Guaraníes (PYG)', 'USD' => 'Dólares (USD)'])->default('PYG')->required(),
                DatePicker::make('valid_until')->native(false)->displayFormat('d/m/Y')->locale('es')->label('Válido hasta')->default(now()->addDays(15))->required(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Productos y servicios')->icon('heroicon-o-squares-plus')->description('Precios con IVA incluido. Revisá los totales antes de guardar. PYG sin decimales; USD hasta 2.')->schema([
                Repeater::make('lines')->live(debounce: 600)->label('Detalle')->hiddenLabel()->compact()->collapsible()->cloneable()
                    ->itemLabel(fn (array $state): string => Str::limit($state['description'] ?? 'Nuevo ítem', 65))->itemNumbers()->schema([
                        Select::make('catalog_item_id')->label('Catálogo (opcional)')->placeholder('Carga manual')->searchable()->preload()
                            ->options(fn (Get $get) => CatalogItem::where('is_active', true)->where('currency', $get('../../currency') ?? 'PYG')->orderBy('name')->limit(25)->get()->mapWithKeys(fn (CatalogItem $item) => [$item->id => $item->selectionLabel()])->all())
                            ->getSearchResultsUsing(fn (string $search, Get $get) => CatalogItem::where('is_active', true)->where('currency', $get('../../currency') ?? 'PYG')
                                ->where(fn (Builder $q) => $q->whereLike('name', '%'.$search.'%')->orWhereLike('sku', '%'.$search.'%'))
                                ->orderBy('name')->limit(25)->get()->mapWithKeys(fn (CatalogItem $item) => [$item->id => $item->selectionLabel()])->all())
                            ->getOptionLabelUsing(fn ($value) => CatalogItem::find($value)?->selectionLabel())
                            ->rules(fn (Get $get) => [Rule::exists('catalog_items', 'id')->where('currency', $get('../../currency') ?? 'PYG')])->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (! $state) {
                                    return;
                                }
                                $item = CatalogItem::where('is_active', true)->find($state);
                                if (! $item || $item->currency !== $get('../../currency')) {
                                    return;
                                }
                                $set('description', $item->quoteDescription());
                                $set('unit_price', $item->unit_price);
                                $set('tax_rate', (string) $item->tax_rate);
                            })->columnSpan(['default' => 1, 'md' => 2, 'xl' => 4]),
                        Textarea::make('description')->label('Descripción')->required()->maxLength(1000)->columnSpan(['default' => 1, 'md' => 2, 'xl' => 8])->rows(2),
                        TextInput::make('quantity')->label('Cantidad')->numeric()->minValue('0.001')->maxValue(999999.999)->default('1')->required()->columnSpan(['xl' => 2]),
                        TextInput::make('unit_price')->label('Precio unitario')->numeric()->minValue(0)->maxValue(fn (Get $get) => $get('../../currency') === 'USD' ? '999999999.99' : '999999999')->required()
                            ->rules(fn (Get $get) => ['regex:'.QuoteCalculator::pricePattern($get('../../currency') ?? 'PYG')])
                            ->validationMessages(['regex' => 'Ingresá un precio válido: PYG entero o USD con hasta 2 decimales.'])->prefix(fn (Get $get) => $get('../../currency') === 'USD' ? 'US$' : '₲')->columnSpan(['xl' => 3]),
                        TextInput::make('discount')->label('Dto. %')->numeric()->minValue(0)->maxValue(100)->default('0')->required()->columnSpan(['xl' => 2]),
                        Select::make('tax_rate')->label('IVA incluido')->options(['0' => 'Exento', '5' => '5%', '10' => '10%'])->default('10')->required()->columnSpan(['xl' => 2]),
                        View::make('filament.quotes.line-total')->viewData(function (Get $get) {
                            $currency = $get('../../currency') ?? 'PYG';
                            try {
                                $values = app(QuoteCalculator::class)->calculate([['description' => $get('description') ?? '', 'quantity' => $get('quantity'), 'unit_price' => $get('unit_price'), 'discount' => $get('discount'), 'tax_rate' => $get('tax_rate')]], $currency);
                                $lineTotal = Quote::money($values['total_minor'], $currency);
                            } catch (ValidationException $e) {
                                $lineTotal = '—';
                            }

                            return compact('lineTotal');
                        })->columnSpan(['xl' => 3]),
                    ])->columns(['default' => 1, 'md' => 2, 'xl' => 12])->minItems(1)->maxItems(100)->defaultItems(1)->addActionLabel('Agregar ítem')->addAction(fn (Action $action) => $action->color('gray')->outlined()->icon('heroicon-o-plus'))->reorderable(false),
            ])->columnSpanFull(),
            View::make('filament.quotes.live-totals')->viewData(function (Get $get) {
                $currency = $get('currency') ?? 'PYG';
                try {
                    $totals = app(QuoteCalculator::class)->calculate($get('lines') ?? [], $currency);
                } catch (ValidationException $e) {
                    $totals = null;
                }

                return compact('totals', 'currency');
            })->columnSpanFull(),
            Section::make('Pago y entrega')->description('Estas condiciones se incluyen en el PDF. El plazo de crédito no genera un vencimiento de cobro automático.')->schema([
                Select::make('payment_mode')->label('Forma de pago')->options(['cash' => 'Contado', 'credit' => 'Crédito', 'to_agree' => 'A convenir'])->default('cash')->required()->live(),
                TextInput::make('credit_days')->label('Plazo de crédito (días)')->integer()->minValue(1)->maxValue(365)->default(30)
                    ->visible(fn (Get $get) => $get('payment_mode') === 'credit')->required(fn (Get $get) => $get('payment_mode') === 'credit'),
                TextInput::make('payment_notes')->label('Detalle del pago')->placeholder('Transferencia, anticipo o desde cuándo se cuentan los días')->maxLength(1000)->columnSpanFull(),
                TextInput::make('delivery_terms')->label('Plazo de entrega')->maxLength(500)->default('A coordinar según disponibilidad y confirmación del pedido.'),
                TextInput::make('warranty_terms')->label('Garantía / soporte incluido')->maxLength(500)->placeholder('Indicá la cobertura aplicable al producto o servicio'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Términos y condiciones')->description('Se copian a esta propuesta. La base se puede ajustar en Mi empresa.')->schema([
                Textarea::make('terms')->label('Términos y condiciones')->rows(7)->maxLength(5000)->default(fn () => QuoteDefaults::terms()),
            ])->collapsible()->collapsed()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('number')->label('Documento / referencia')->getStateUsing(fn (Quote $record) => $record->reference())->description(fn (Quote $record) => Str::limit($record->subject, 64))->tooltip(fn (Quote $record) => $record->subject)->weight(FontWeight::SemiBold)->searchable(query: function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->whereLike('subject', '%'.$search.'%');
                    if (preg_match('/^(?:PPTO-0*|Borrador\s*#)?([0-9]{1,18})$/i', trim($search), $match)) {
                        $value = ltrim($match[1], '0') ?: '0';
                        str_starts_with(strtolower(trim($search)), 'borrador')
                            ? $q->orWhere(fn (Builder $draft) => $draft->where('status', 'draft')->where('id', $value))
                            : $q->orWhere('number', $value);
                    }
                });
            })->sortable()->extraAttributes(['class' => 'sv-document-column']),
            TextColumn::make('customer.name')->label('Cliente')->formatStateUsing(fn ($state, Quote $record) => $record->snapshot['customer']['name'] ?? $state)->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->whereHas('customer', fn (Builder $customer) => $customer->whereLike('name', '%'.$search.'%'))
                ->orWhereLike('snapshot->customer->name', '%'.$search.'%')))->sortable()->wrap(),
            TextColumn::make('issued_at')->label('Emisión')->date('d/m/Y')->placeholder('Sin emitir')->sortable()->toggleable(),
            TextColumn::make('valid_until')->label('Vigencia')->date('d/m/Y')->sortable()->toggleable(),
            TextColumn::make('status')->label('Estado')->description(fn (Quote $record) => $record->status === 'issued' && $record->valid_until->isBefore(today()) ? 'Vencido · sin respuesta' : null)->formatStateUsing(fn ($record) => $record->statusLabel())->color(fn (string $state) => match ($state) {
                'accepted' => 'success','rejected','cancelled' => 'danger',default => 'gray'
            }),
            TextColumn::make('total_minor')->label('Total')->formatStateUsing(fn ($record) => $record->formattedTotal())->alignEnd()->weight(FontWeight::SemiBold)->extraAttributes(['class' => 'sv-amount']),
            TextColumn::make('currency')->label('Moneda')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('currency')->label('Moneda')->options(['PYG' => 'Guaraníes', 'USD' => 'Dólares']),
            Filter::make('period')->label('Período de emisión')->schema([
                DatePicker::make('from')->label('Desde')->native(),
                DatePicker::make('until')->label('Hasta')->native()->afterOrEqual('from'),
            ])->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('issued_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('issued_at', '<=', $date)))
                ->indicateUsing(fn (array $data) => array_filter([
                    isset($data['from']) ? 'Desde: '.$data['from'] : null,
                    isset($data['until']) ? 'Hasta: '.$data['until'] : null,
                ])),
            Filter::make('this_month')->label('Emitidos este mes')->query(fn (Builder $q) => $q->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()])),
            Filter::make('expired')->label('Vencidos sin respuesta')->query(fn (Builder $q) => $q->where('status', 'issued')->whereDate('valid_until', '<', today())),
        ])->recordUrl(fn (Quote $record) => self::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')->label('Ver detalle')->icon('heroicon-o-document-text')->url(fn (Quote $record) => self::getUrl('view', ['record' => $record])),
                    ...self::documentActions(),
                ])->label('Acciones')->icon('heroicon-o-ellipsis-horizontal')->color('gray')->iconButton()->tooltip('Acciones del presupuesto'),
            ])->toolbarActions([
                BulkAction::make('openSelected')->label('Ver detalle')->icon('heroicon-o-document-text')->color('gray')
                    ->action(fn (Collection $records, $livewire) => $livewire->redirect(self::getUrl('view', ['record' => $records->first()]))),
                BulkAction::make('editSelected')->label('Editar borrador')->icon('heroicon-o-pencil-square')->color('gray')
                    ->visible(fn () => self::canCreate())->action(function (Collection $records, $livewire) {
                        $record = $records->first();
                        if (! self::canEdit($record)) {
                            Notification::make()->title('Solo se pueden editar borradores')->warning()->send();

                            return;
                        }
                        $livewire->redirect(self::getUrl('edit', ['record' => $record]));
                    }),
                BulkAction::make('previewSelected')->label('Vista previa PDF')->icon('heroicon-o-document-magnifying-glass')->color('gray')
                    ->action(fn (Collection $records, $livewire) => $livewire->redirect(route('quotes.preview', $records->first()))),
                BulkAction::make('historySelected')->label('Historial')->icon('heroicon-o-clock')->color('gray')
                    ->action(fn (Collection $records, $livewire) => $livewire->redirect(route('quotes.history', $records->first()))),
            ])->maxSelectableRecords(1)->selectCurrentPageOnly()->deselectAllRecordsWhenFiltered()
            ->recordActionsColumnLabel('Opciones')->stackedOnMobile()->splitSearchTerms(false)->searchPlaceholder('Buscar documento o cliente')
            ->paginationPageOptions([10, 25, 50])->defaultPaginationPageOption(10)->defaultSort('id', 'desc')
            ->emptyStateIcon('heroicon-o-document-text')->emptyStateHeading('No hay presupuestos para mostrar')
            ->emptyStateDescription('Revisá la búsqueda y los filtros o prepará una nueva propuesta.');
    }

    public static function documentActions(): array
    {
        return [
            Action::make('editDraft')->label('Editar borrador')->icon('heroicon-o-pencil-square')->color('gray')
                ->visible(fn (Quote $record) => self::canEdit($record))->url(fn (Quote $record) => self::getUrl('edit', ['record' => $record])),
            Action::make('preview')->label('Vista previa PDF')->icon('heroicon-o-document-magnifying-glass')->color('gray')
                ->url(fn (Quote $record) => route('quotes.preview', $record))->openUrlInNewTab(),
            Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn (Quote $record) => route('quotes.pdf', $record))->openUrlInNewTab(),
            Action::make('issue')->label('Emitir presupuesto')->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()->modalHeading('Emitir presupuesto definitivo')
                ->modalDescription('Se asignará el siguiente correlativo y se conservará el PDF con los datos actuales. El documento dejará de ser editable. Esta acción no envía correos.')
                ->modalSubmitActionLabel('Confirmar emisión')
                ->visible(fn (Quote $record) => self::canCreate() && $record->status === 'draft')
                ->action(function (Quote $record, $livewire) {
                    app(QuoteService::class)->issue(auth()->user(), $record);
                    Notification::make()->title('Presupuesto emitido')->success()->send();
                    $livewire->redirect(self::getUrl('view', ['record' => $record]));
                }),
            Action::make('decision')->label('Registrar decisión')->icon('heroicon-o-check-circle')->color('gray')
                ->visible(fn (Quote $record) => self::canCreate() && $record->status === 'issued')->schema([
                    Select::make('status')->label('Resultado')->options(['accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'cancelled' => 'Anulado'])->required(),
                    Textarea::make('evidence')->label('Evidencia / referencia')->helperText('Indicá fecha, contacto y referencia del correo, ticket o aprobación.')->minLength(10)->maxLength(2000)->required(),
                ])->action(function (Quote $record, array $data, $livewire) {
                    app(QuoteService::class)->decide(auth()->user(), $record, $data['status'], $data['evidence']);
                    Notification::make()->title('Decisión registrada')->success()->send();
                    $livewire->redirect(self::getUrl('view', ['record' => $record]));
                }),
            Action::make('deleteDraft')->label('Eliminar borrador')->icon('heroicon-o-trash')->color('danger')->outlined()->requiresConfirmation()
                ->modalHeading('Eliminar este borrador')->modalDescription('El borrador dejará de aparecer en el listado. Se conservará un registro de auditoría de esta operación. No afecta presupuestos emitidos ni consume un correlativo.')
                ->modalSubmitActionLabel('Eliminar borrador')->visible(fn (Quote $record) => self::canDelete($record))
                ->action(function (Quote $record, $livewire) {
                    app(QuoteService::class)->deleteDraft(auth()->user(), $record);
                    Notification::make()->title('Borrador eliminado')->success()->send();
                    $livewire->redirect(self::getUrl('index'));
                }),
            Action::make('history')->label('Historial')->icon('heroicon-o-clock')->color('gray')
                ->url(fn (Quote $record) => route('quotes.history', $record))->openUrlInNewTab(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => QuoteResource\Pages\ListQuotes::route('/'), 'create' => QuoteResource\Pages\CreateQuote::route('/create'), 'edit' => QuoteResource\Pages\EditQuote::route('/{record}/edit'), 'view' => QuoteResource\Pages\ViewQuote::route('/{record}')];
    }
}
