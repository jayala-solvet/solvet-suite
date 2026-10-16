<?php

namespace App\Filament\Pages;

use App\Modules\Legal\Models\LegalEvent;
use App\Modules\Legal\Services\LegalService;
use Filament\Pages\Page;
use Livewire\WithPagination;

class LegalCenter extends Page
{
    use WithPagination;

    protected string $view = 'filament.legal.center';

    protected static ?string $navigationLabel = 'Privacidad';

    protected static ?string $title = 'Documentos y privacidad';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-document-check';

    public function documents()
    {
        return LegalService::current();
    }

    public function history()
    {
        return LegalEvent::with('document')->where('user_id', auth()->id())->latest('id')->paginate(25);
    }

    public function lastAction(int $documentId): ?string
    {
        return LegalEvent::where('user_id', auth()->id())->where('legal_document_id', $documentId)->latest('id')->value('action');
    }

    public function record(int $documentId, string $action): void
    {
        app(LegalService::class)->record(auth()->user(), $documentId, $action);
    }
}
