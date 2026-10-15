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
    Route::get('/quotes/{quote}/pdf', function (Quote $quote) {
        if ($quote->pdf_path) {
            abort_unless(Storage::disk('local')->exists($quote->pdf_path), 404);

            return Storage::disk('local')->download($quote->pdf_path, $quote->reference().'.pdf');
        }
        $quote->snapshot = ['company' => Company::firstOrFail()->toArray(), 'customer' => $quote->customer->toArray()];

        return Pdf::loadView('pdf.quote', ['quote' => $quote, 'draft' => true])->setPaper('a4')->download('BORRADOR-'.$quote->id.'.pdf');
    })->name('quotes.pdf');
    Route::get('/quotes/{quote}/history', fn (Quote $quote) => view('quotes.history', ['quote' => $quote, 'events' => AuditEvent::where('subject_type', 'quote')->where('subject_id', $quote->id)->orderBy('id')->get()]))->name('quotes.history');
});
