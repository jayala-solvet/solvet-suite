<?php

namespace App\Http\Middleware;

use App\Modules\Legal\Models\LegalDocument;
use App\Modules\Legal\Models\LegalEvent;
use Closure;
use Illuminate\Http\Request;

class RequireContractAcceptance
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('livewire*', 'app/legal-center', 'app/profile', 'app/logout', 'app/multi-factor-authentication*')) {
            return $next($request);
        }
        $document = LegalDocument::where('effect', 'contract')->whereNotNull('published_at')->latest('id')->first();
        if ($document && ! LegalEvent::where('user_id', $request->user()->id)->where('legal_document_id', $document->id)->where('action', 'accepted')->exists()) {
            return redirect('/app/legal-center');
        }

        return $next($request);
    }
}
