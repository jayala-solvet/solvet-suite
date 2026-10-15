<?php

namespace Tests\Feature;

use App\Filament\Resources\QuoteResource\Pages\ListQuotes;
use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private function draft(string $currency = 'PYG')
    {
        return app(QuoteService::class)->saveDraft($this->admin, [
            'customer_id' => Customer::firstOrCreate(['name' => 'Cliente original'])->id,
            'subject' => 'Servicio de infraestructura', 'currency' => $currency,
            'valid_until' => today()->addDays(15)->toDateString(), 'terms' => 'Pago a 15 días.',
            'lines' => [['description' => 'Instalación de red', 'quantity' => '2', 'unit_price' => '11000', 'discount' => '0', 'tax_rate' => '10']],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Administrador', 'email' => 'admin@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        Company::create(['name' => 'Solvet Test']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 1]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
    }

    public function test_list_tabs_search_currency_and_period_filters(): void
    {
        $draft = $this->draft();
        $issued = app(QuoteService::class)->issue($this->admin, $this->draft('USD'));
        Livewire::test(ListQuotes::class)->assertCanSeeTableRecords([$draft, $issued])
            ->set('activeTab', 'draft')->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$issued])
            ->set('activeTab', 'all')->filterTable('currency', 'USD')
            ->assertCanSeeTableRecords([$issued])->assertCanNotSeeTableRecords([$draft])
            ->resetTableFilters()->filterTable('period', ['from' => today()->toDateString(), 'until' => today()->toDateString()])
            ->assertCanSeeTableRecords([$issued])->assertCanNotSeeTableRecords([$draft])
            ->resetTableFilters()->searchTable('Cliente original')->assertCanSeeTableRecords([$draft, $issued])
            ->searchTable($issued->reference())->assertCanSeeTableRecords([$issued])->assertCanNotSeeTableRecords([$draft])
            ->searchTable($draft->reference())->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$issued]);
        $issued->customer->update(['name' => 'Nombre actualizado']);
        Livewire::test(ListQuotes::class)->searchTable('Cliente original')->assertCanSeeTableRecords([$issued]);
    }

    public function test_issue_from_detail_then_decide_and_keep_original_preview(): void
    {
        $quote = $this->draft();
        Livewire::test(ViewQuote::class, ['record' => $quote->id])
            ->assertSee('Instalación de red')->assertSee('₲ 22.000')
            ->callAction('issue')->assertHasNoActionErrors();
        $quote->refresh();
        $this->assertSame('issued', $quote->status);
        $bytes = Storage::disk('local')->get($quote->pdf_path);
        $quote->customer->update(['name' => 'Cliente actualizado']);
        Livewire::test(ViewQuote::class, ['record' => $quote->id])->assertSee('Cliente original')
            ->callAction('decision', data: ['status' => 'accepted', 'evidence' => 'Aceptado por correo por el contacto.'])->assertHasNoActionErrors();
        $this->assertSame('accepted', $quote->fresh()->status);
        $response = $this->get(route('quotes.preview', $quote))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertSame($bytes, $response->streamedContent());
        $this->get(route('quotes.history', $quote))->assertOk()->assertSee('Aceptación registrada')->assertSee('Aceptado por correo');
    }

    public function test_selected_document_actions_and_reader_permissions(): void
    {
        $quote = $this->draft();
        Livewire::test(ListQuotes::class)->callTableBulkAction('openSelected', [$quote])
            ->assertRedirect('/app/quotes/'.$quote->id);
        Livewire::test(ListQuotes::class)->callTableBulkAction('editSelected', [$quote])
            ->assertRedirect('/app/quotes/'.$quote->id.'/edit');
        $reader = User::create(['name' => 'Consulta', 'email' => 'reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->actingAs($reader);
        Livewire::test(ViewQuote::class, ['record' => $quote->id])->assertActionHidden('issue')->assertActionHidden('editDraft');
        $this->get('/app/quotes/'.$quote->id.'/edit')->assertForbidden();
        $this->get('/app/quotes/'.$quote->id)->assertOk();
        $this->get(route('quotes.preview', $quote))->assertOk();
    }

    public function test_expiry_filter_does_not_change_the_commercial_status(): void
    {
        $quote = app(QuoteService::class)->issue($this->admin, $this->draft());
        $this->travel(20)->days();
        Livewire::test(ListQuotes::class)->filterTable('expired')->assertCanSeeTableRecords([$quote]);
        $this->assertSame('issued', $quote->fresh()->status);
        $this->get('/app/quotes/'.$quote->id)->assertOk()->assertSee('Vencido');
    }
}
