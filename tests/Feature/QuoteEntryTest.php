<?php

namespace Tests\Feature;

use App\Filament\Resources\CatalogItemResource\Pages\CreateCatalogItem;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Services\CustomerService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteDefaults;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Admin', 'email' => 'entry@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        $this->customer = Customer::create(['name' => 'Cliente de prueba']);
        Company::create(['name' => 'SOLVET-IT']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 10]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
    }

    private function data(): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Red y soporte', 'currency' => 'PYG', 'valid_until' => today()->addDays(15)->toDateString(), 'payment_mode' => 'credit', 'credit_days' => 30, 'payment_notes' => 'Desde la fecha de factura.', 'delivery_terms' => '5 días hábiles', 'warranty_terms' => '30 días sobre configuración', 'lines' => [['description' => 'Instalación de red', 'quantity' => '2', 'unit_price' => '110000', 'discount' => '10', 'tax_rate' => '10']]];
    }

    private function itemData(): array
    {
        return ['sku' => 'RED-01', 'name' => 'Servicio de red', 'description' => 'Configuración de red', 'kind' => 'service', 'currency' => 'PYG', 'unit_price' => '110000', 'tax_rate' => '10', 'is_active' => true];
    }

    public function test_inline_customer_creation_preserves_quote_input_and_checks_permissions(): void
    {
        $page = Livewire::test(CreateQuote::class)->fillForm(['subject' => 'Carga en curso']);
        $page->callFormComponentAction('customer_id', 'createOption', data: ['name' => 'Cliente desde presupuesto', 'email' => 'nuevo@example.test'])
            ->assertHasNoFormErrors()->assertFormSet(['subject' => 'Carga en curso']);
        $this->assertDatabaseHas('customers', ['name' => 'Cliente desde presupuesto']);
        $page->assertFormSet(['customer_id' => Customer::where('name', 'Cliente desde presupuesto')->value('id')]);
        $reader = User::create(['name' => 'Consulta', 'email' => 'inline-reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->expectException(AuthorizationException::class);
        app(CustomerService::class)->create($reader, ['name' => 'No permitido']);
    }

    public function test_catalogue_price_and_credit_bounds_return_field_errors(): void
    {
        $item = $this->itemData();
        $item['unit_price'] = '1.50';
        Livewire::test(CreateCatalogItem::class)->fillForm($item)->call('create')->assertHasFormErrors(['unit_price']);
        foreach ([0, 366, '30.5'] as $days) {
            $data = $this->data();
            $data['credit_days'] = $days;
            try {
                app(QuoteService::class)->saveDraft($this->admin, $data);
                $this->fail('Plazo inválido');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('credit_days', $e->errors());
            }
        }
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_credit_validation_cash_reset_and_default_terms(): void
    {
        $data = $this->data();
        $data['credit_days'] = null;
        try {
            app(QuoteService::class)->saveDraft($this->admin, $data);
            $this->fail('Crédito sin días debe rechazarse');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('credit_days', $e->errors());
            $this->assertDatabaseCount('quotes', 0);
        }
        $quote = app(QuoteService::class)->saveDraft($this->admin, $this->data());
        $this->assertSame('Crédito a 30 días', $quote->paymentLabel());
        $this->assertStringContainsString('José María Ayala Benítez', $quote->terms);
        $data = $this->data();
        $data['payment_mode'] = 'cash';
        $quote = app(QuoteService::class)->saveDraft($this->admin, $data, $quote);
        $this->assertNull($quote->credit_days);
        $this->assertSame('Contado', $quote->paymentLabel());
    }

    public function test_catalogue_copy_is_optional_and_stored_lines_survive_catalogue_changes(): void
    {
        $item = app(CatalogService::class)->save($this->admin, $this->itemData());
        $data = $this->data();
        $data['lines'][0]['catalog_item_id'] = $item->id;
        $quote = app(QuoteService::class)->saveDraft($this->admin, $data);
        $this->assertSame('RED-01', $quote->lines[0]['sku']);
        $this->assertSame('198000', $quote->total_minor);
        $quote = app(QuoteService::class)->issue($this->admin, $quote);
        $bytes = Storage::disk('local')->get($quote->pdf_path);
        $item->update(['name' => 'Nuevo nombre', 'price_minor' => '999', 'is_active' => false]);
        Company::first()->update(['quote_terms' => 'Condiciones nuevas para propuestas nuevas.']);
        $this->assertSame('110000', $quote->fresh()->lines[0]['unit_price']);
        $this->assertSame('credit', $quote->fresh()->payment_mode);
        $this->assertSame($bytes, Storage::disk('local')->get($quote->pdf_path));
        $this->assertSame('Condiciones nuevas para propuestas nuevas.', QuoteDefaults::terms());
        $manual = app(QuoteService::class)->saveDraft($this->admin, $this->data());
        $this->assertNull($manual->lines[0]['catalog_item_id']);
    }

    public function test_catalogue_currency_mismatch_is_rejected_without_conversion(): void
    {
        $itemData = $this->itemData();
        $itemData['currency'] = 'USD';
        $itemData['unit_price'] = '10.50';
        $item = app(CatalogService::class)->save($this->admin, $itemData);
        $this->assertSame('1050', $item->price_minor);
        $this->assertSame('10.50', $item->unit_price);
        $data = $this->data();
        $data['lines'][0]['catalog_item_id'] = $item->id;
        $this->expectException(ValidationException::class);
        app(QuoteService::class)->saveDraft($this->admin, $data);
    }

    public function test_delete_draft_is_audited_and_private_routes_no_longer_resolve(): void
    {
        $quote = app(QuoteService::class)->saveDraft($this->admin, $this->data());
        Livewire::test(ViewQuote::class, ['record' => $quote->id])->callAction('deleteDraft')->assertHasNoActionErrors()->assertRedirect('/app/quotes');
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
        $this->assertNull(Quote::find($quote->id));
        $this->assertSame('draft.deleted', AuditEvent::latest('id')->first()->action);
        $this->assertSame($this->admin->id, AuditEvent::latest('id')->first()->actor_id);
        $this->assertSame(10, (int) DB::table('document_sequences')->where('key', 'quote')->value('next_number'));
        $this->get('/app/quotes/'.$quote->id)->assertNotFound();
        $this->get(route('quotes.preview', $quote))->assertNotFound();
        $this->get(route('quotes.history', $quote))->assertNotFound();
    }

    public function test_issued_quote_cannot_be_deleted_and_reader_cannot_delete_or_change_catalogue(): void
    {
        $service = app(QuoteService::class);
        $issued = $service->issue($this->admin, $service->saveDraft($this->admin, $this->data()));
        Livewire::test(ViewQuote::class, ['record' => $issued->id])->assertActionHidden('deleteDraft');
        try {
            $service->deleteDraft($this->admin, $issued);
            $this->fail('No se puede eliminar un emitido');
        } catch (ValidationException $e) {
            $this->assertNull($issued->fresh()->deleted_at);
        }
        $reader = User::create(['name' => 'Consulta', 'email' => 'reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $draft = $service->saveDraft($this->admin, $this->data());
        try {
            $service->deleteDraft($reader, $draft);
            $this->fail('Lector no puede eliminar');
        } catch (AuthorizationException $e) {
            $this->assertNull($draft->fresh()->deleted_at);
        }
        $this->expectException(AuthorizationException::class);
        app(CatalogService::class)->save($reader, $this->itemData());
    }

    public function test_livewire_catalogue_create_and_quote_credit_persist(): void
    {
        Livewire::test(CreateCatalogItem::class)->fillForm($this->itemData())->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('catalog_items', ['sku' => 'RED-01', 'price_minor' => '110000']);
        Livewire::test(CreateQuote::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('quotes', ['payment_mode' => 'credit', 'credit_days' => 30, 'total_minor' => '198000']);
        $this->get('/app/catalog-items')->assertOk();
        $this->get('/app/catalog-items/create')->assertOk();
        $this->get('/app/catalog-items/'.CatalogItem::first()->id.'/edit')->assertOk();
    }
}
