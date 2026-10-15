<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Modules\Core\Models\AuditEvent;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected string $view = 'filament.quotes.view';

    public function getTitle(): string
    {
        return $this->getRecord()->reference();
    }

    protected function getHeaderActions(): array
    {
        $actions = QuoteResource::documentActions();
        $issue = collect($actions)->first(fn (Action $action) => $action->getName() === 'issue');

        return [
            Action::make('back')->label('Presupuestos')->color('gray')->url(QuoteResource::getUrl('index')),
            ActionGroup::make(array_values(array_filter($actions, fn (Action $action) => $action->getName() !== 'issue')))->label('Acciones del documento')->button()->color('gray'),
            $issue,
        ];
    }

    public function events()
    {
        return AuditEvent::with('actor')->where('subject_type', 'quote')
            ->where('subject_id', $this->getRecord()->id)->orderByDesc('id')->get();
    }
}
