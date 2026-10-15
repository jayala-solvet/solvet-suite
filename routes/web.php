<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Quotes\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/app');
Route::middleware(['auth', EnsureActiveUser::class])->group(function () {
    $document = function (Quote $quote) {
        $preview = request()->routeIs('quotes.preview');
        if ($quote->pdf_path) {
            abort_unless(Storage::disk('local')->exists($quote->pdf_path), 404);

            return $preview
                ? Storage::disk('local')->response($quote->pdf_path, $quote->reference().'.pdf', ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'])
                : Storage::disk('local')->download($quote->pdf_path, $quote->reference().'.pdf');
        }
        abort_unless($quote->status === 'draft', 404);
        $quote->snapshot = ['company' => Company::firstOrFail()->toArray(), 'customer' => $quote->customer->toArray()];
        $pdf = Pdf::loadView('pdf.quote', ['quote' => $quote, 'draft' => true])->setPaper('a4');

        return $preview ? $pdf->stream('BORRADOR-'.$quote->id.'.pdf') : $pdf->download('BORRADOR-'.$quote->id.'.pdf');
    };
    Route::get('/quotes/{quote}/pdf', $document)->name('quotes.pdf');
    Route::get('/quotes/{quote}/preview', $document)->name('quotes.preview');
    Route::get('/quotes/{quote}/history', fn (Quote $quote) => view('quotes.history', ['quote' => $quote, 'events' => AuditEvent::with('actor')->where('subject_type', 'quote')->where('subject_id', $quote->id)->orderBy('id')->get()]))->name('quotes.history');
});
