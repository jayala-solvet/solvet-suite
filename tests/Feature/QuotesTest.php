<?php

namespace Tests\Feature;

use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Models\User;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteCalculator;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuotesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Test Admin', 'email' => 'test@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        $this->customer = Customer::create(['name' => 'Cliente inicial', 'tax_id' => 'RUC-DEMO']);
        Company::create(['name' => 'Solvet Test']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 1]);
    }

    private function data(): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Prueba', 'currency' => 'PYG', 'valid_until' => today()->addDays(15)->toDateString(), 'terms' => 'Condiciones', 'lines' => [['description' => 'Servicio', 'quantity' => '2', 'unit_price' => '110000', 'discount' => '10', 'tax_rate' => '10']]];
    }

    public function test_calculates_included_tax_and_discount_without_binary_floats(): void
    {
        $data = $this->data();
        $totals = app(QuoteCalculator::class)->calculate($data['lines'], 'PYG');
        $this->assertSame('220000', $totals['subtotal_minor']);
        $this->assertSame('22000', $totals['discount_minor']);
        $this->assertSame('198000', $totals['total_minor']);
        $this->assertSame('18000', $totals['tax_minor']);
        $data['lines'][0] = ['description' => 'USD', 'quantity' => '3', 'unit_price' => '0.10', 'discount' => '0', 'tax_rate' => '0'];
        $this->assertSame('30', app(QuoteCalculator::class)->calculate($data['lines'], 'USD')['total_minor']);
    }

    public function test_issued_pdf_is_preserved_when_customer_changes(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->saveDraft($this->admin, $this->data());
        $quote = $service->issue($this->admin, $quote);
        $bytes = Storage::disk('local')->get($quote->pdf_path);
        $this->assertSame(1, $quote->number);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertSame(hash('sha256', $bytes), $quote->pdf_sha256);
        $this->customer->update(['name' => 'Nombre nuevo']);
        $this->assertSame('Cliente inicial', $quote->fresh()->snapshot['customer']['name']);
        $this->assertSame($bytes, Storage::disk('local')->get($quote->pdf_path));
        $this->assertDatabaseCount('audit_events', 2);
    }

    public function test_issued_quote_cannot_be_edited_or_issued_twice(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->issue($this->admin, $service->saveDraft($this->admin, $this->data()));
        try {
            $service->issue($this->admin, $quote);
            $this->fail('No debe emitir dos veces');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $this->assertSame(2, (int) DB::table('document_sequences')->where('key', 'quote')->value('next_number'));
        $this->expectException(ValidationException::class);
        $service->saveDraft($this->admin, $this->data(), $quote);
    }

    public function test_reader_cannot_create_quotes(): void
    {
        $reader = User::create(['name' => 'Reader', 'email' => 'reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->expectException(AuthorizationException::class);
        app(QuoteService::class)->saveDraft($reader, $this->data());
    }

    public function test_decision_requires_evidence_and_is_audited(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->issue($this->admin, $service->saveDraft($this->admin, $this->data()));
        try {
            $service->decide($this->admin, $quote, 'accepted', '');
            $this->fail('Debe exigir evidencia');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('evidence', $e->errors());
        }
        $quote = $service->decide($this->admin, $quote, 'accepted', 'Correo confirmado por contacto comercial.');
        $this->assertSame('accepted', $quote->status);
        $this->assertSame('quote.accepted', AuditEvent::latest('id')->first()->action);
    }

    public function test_pdf_is_not_public_and_inactive_users_are_denied(): void
    {
        $quote = app(QuoteService::class)->saveDraft($this->admin, $this->data());
        $this->get(route('quotes.pdf', $quote))->assertRedirect('/app/login');
        $this->admin->update(['is_active' => false]);
        $this->actingAs($this->admin)->get(route('quotes.pdf', $quote))->assertForbidden();
    }

    public function test_authenticated_pages_render(): void
    {
        $this->actingAs($this->admin);
        foreach (['/app/workspace', '/app/quotes', '/app/quotes/create', '/app/customers', '/app/customers/create', '/app/companies'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guarani_fractional_price_is_rejected(): void
    {
        $data = $this->data();
        $data['lines'][0]['unit_price'] = '1.50';
        $this->expectException(ValidationException::class);
        app(QuoteCalculator::class)->calculate($data['lines'], 'PYG');
    }

    public function test_large_amount_is_formatted_without_losing_digits(): void
    {
        $this->assertSame('₲ 99.999.999.800.100.000.000', Quote::money('99999999800100000000', 'PYG'));
    }

    public function test_invalid_quantity_does_not_save_any_document(): void
    {
        $data = $this->data();
        $data['lines'][0]['quantity'] = '-1';
        try {
            app(QuoteService::class)->saveDraft($this->admin, $data);
            $this->fail('Cantidad negativa rechazada');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('quotes', 0);
        }
    }

    public function test_resource_livewire_create_persists_calculated_values(): void
    {
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
        Livewire::test(CreateQuote::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $quote = Quote::firstOrFail();
        $this->assertSame('198000',$quote->total_minor);
        $this->assertSame('draft',$quote->status);
    }
}
