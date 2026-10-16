<?php

namespace App\Filament\Resources;

use App\Modules\Operations\Models\SalesOrder;
use App\Modules\Quotes\Models\Quote;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'Pedidos y ejecución';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-clipboard-document-check';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('orders.view') || auth()->user()?->allows('orders.view-own');
    }

    public static function canView($record): bool
    {
        return auth()->user()?->allows('orders.view') || (auth()->user()?->allows('orders.view-own') && $record->assigned_to === auth()->id());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->allows('orders.view') ? $query : $query->where('assigned_to', auth()->id());
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
        return $table->columns([
            TextColumn::make('id')->label('Pedido')->formatStateUsing(fn (SalesOrder $record) => $record->reference())->sortable(),
            TextColumn::make('quote.subject')->label('Trabajo')->description(fn (SalesOrder $record) => $record->quote->reference())->searchable()->wrap(),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn ($state) => ['confirmed' => 'En ejecución', 'completed' => 'Completado', 'cancelled' => 'Anulado'][$state] ?? $state),
            TextColumn::make('sales_minor')->label('Venta acordada con IVA')->visible(fn () => auth()->user()?->allows('orders.view'))->formatStateUsing(fn (SalesOrder $record) => Quote::money($record->sales_minor, $record->currency))->alignEnd(),
            TextColumn::make('created_at')->label('Confirmación')->date('d/m/Y'),
        ])->modifyQueryUsing(fn (Builder $query) => $query->with('quote'))->recordUrl(fn (SalesOrder $record) => self::getUrl('view', ['record' => $record]))->recordActions([Action::make('view')->label('Ver ejecución')->url(fn (SalesOrder $record) => self::getUrl('view', ['record' => $record]))])->stackedOnMobile()->defaultSort('id', 'desc')->emptyStateHeading('Todavía no hay pedidos')->emptyStateDescription('Abrí un presupuesto aceptado y confirmá su pedido. Un borrador no reserva stock.');
    }

    public static function getPages(): array
    {
        return ['index' => SalesOrderResource\Pages\ListSalesOrders::route('/'), 'view' => SalesOrderResource\Pages\ViewSalesOrder::route('/{record}')];
    }
}
