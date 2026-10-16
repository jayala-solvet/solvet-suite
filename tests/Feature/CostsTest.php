<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\CostProfileResource\Pages\CreateCostProfile;
use App\Filament\Resources\CostProfileResource\Pages\ListCostProfiles;
use App\Filament\Resources\QuoteResource\Pages\EditQuote;
use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Costs\Models\CostProfile;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Costs\Services\CostService;
use App\Modules\Customers\Models\Customer;
use App\Modules\Dashboard\Services\CommercialDashboard;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CostsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CatalogItem $item;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Admin', 'email' => 'costs@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
        Company::create(['name' => 'SOLVET-IT']);
        $this->customer = Customer::create(['name' => 'Cliente de costos']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 1]);
        $this->item = app(CatalogService::class)->save($this->admin, ['name' => 'Switch', 'kind' => 'product', 'unit' => 'unit', 'currency' => 'PYG', 'unit_price' => '550000', 'tax_rate' => '10', 'is_active' => true]);
    }

    private function data(array $overrides = []): array
    {
        return $overrides + ['catalog_item_id' => $this->item->id, 'source' => 'purchase', 'supplier_name' => 'Distribuidor confidencial', 'supplier_tax_id' => '80000000-0', 'reference' => 'COT-INTERNA-001', 'unit_cost' => '500000', 'extra_cost' => '0', 'purchase_tax_rate' => '10', 'vat_treatment' => 'recoverable', 'pricing_mode' => 'margin', 'pricing_value' => '10', 'cost_date' => today()->toDateString(), 'valid_until' => today()->addDays(5)->toDateString(), 'apply_price' => true];
    }

    private function draft(array $line = []): Quote
    {
        return app(QuoteService::class)->saveDraft($this->admin, ['customer_id' => $this->customer->id, 'subject' => 'Switch y soporte', 'currency' => 'PYG', 'valid_until' => today()->addDays(15)->toDateString(), 'lines' => [$line + ['catalog_item_id' => $this->item->id, 'description' => 'Switch', 'quantity' => '1', 'unit_price' => '550000', 'discount' => '0', 'tax_rate' => '10']]]);
    }

    public function test_vat_margin_markup_fixed_profit_and_exact_currency_calculations(): void
    {
        $calculator = app(CostCalculator::class);
        $fixed = $calculator->calculate($this->data(['pricing_mode' => 'fixed_profit', 'pricing_value' => '50000']), 'PYG', 10);
        $this->assertSame('454545.45454545', $fixed['effective_unit_minor']);
        $this->assertSame('555000', $fixed['suggested_price_minor']);
        $markup = $calculator->calculate($this->data(['pricing_mode' => 'markup', 'pricing_value' => '10']), 'PYG', 10);
        $this->assertSame('550000', $markup['suggested_price_minor']);
        $margin = $calculator->calculate($this->data(['pricing_value' => '20']), 'PYG', 10);
        $this->assertSame('625000', $margin['suggested_price_minor']);
        $noCredit = $calculator->calculate($this->data(['vat_treatment' => 'non_recoverable', 'pricing_mode' => 'markup', 'pricing_value' => '10']), 'PYG', 10);
        $this->assertSame('500000.00000000', $noCredit['effective_unit_minor']);
        $this->assertSame('605000', $noCredit['suggested_price_minor']);
        $usd = $calculator->calculate($this->data(['unit_cost' => '0.11', 'pricing_mode' => 'fixed_profit', 'pricing_value' => '0.01']), 'USD', 10);
        $this->assertSame('13', $usd['suggested_price_minor']);
        $zero = $calculator->calculate($this->data(['unit_cost' => '0', 'pricing_value' => '0']), 'PYG', 10);
        $this->assertSame('0', $zero['suggested_price_minor']);
        foreach ([['pricing_value' => '100'], ['pricing_value' => '-1'], ['unit_cost' => '2.50'], ['pricing_mode' => 'fixed_profit', 'pricing_value' => '1.50'], ['source' => 'own'], ['unit_cost' => '999999999', 'pricing_value' => '99.99']] as $bad) {
            try {
                $calculator->calculate($this->data($bad), 'PYG', 10);
                $this->fail('Invalid cost accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_versions_audit_catalog_price_pending_vat_and_dimension_guards(): void
    {
        $first = app(CostService::class)->create($this->admin, $this->data());
        $this->assertSame('555556', $this->item->fresh()->price_minor);
        $second = app(CostService::class)->create($this->admin, $this->data(['unit_cost' => '600000', 'apply_price' => false]));
        $this->assertSame($second->id, $this->item->fresh()->current_cost_id);
        $this->assertSame('500000', $first->fresh()->purchase_minor);
        $this->assertSame('505051', app(CostCalculator::class)->priceForProfile($first, 0));
        $this->assertSame(2, AuditEvent::where('action', 'cost.created')->count());
        $pending = app(CostService::class)->create($this->admin, $this->data(['vat_treatment' => 'pending', 'apply_price' => false]));
        $this->assertNull($pending->effective_unit_minor);
        $this->assertSame('555556', $this->item->fresh()->price_minor);
        $this->assertFalse($this->draft()->cost_snapshot['complete']);
        try {
            app(CostService::class)->create($this->admin, $this->data(['vat_treatment' => 'pending']));
            $this->fail('Pending VAT price applied');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('apply_price', $e->errors());
        }
        try {
            app(CatalogService::class)->save($this->admin, ['name' => 'Switch', 'kind' => 'product', 'unit' => 'hour', 'currency' => 'PYG', 'unit_price' => '550000', 'tax_rate' => '10', 'is_active' => true], $this->item);
            $this->fail('Unit changed');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_own_and_outsourced_hourly_support(): void
    {
        $service = app(CatalogService::class)->save($this->admin, ['name' => 'Soporte N2 / hora', 'kind' => 'service', 'unit' => 'hour', 'currency' => 'PYG', 'unit_price' => '110000', 'tax_rate' => '10', 'is_active' => true]);
        $own = app(CostService::class)->create($this->admin, $this->data(['catalog_item_id' => $service->id, 'source' => 'own', 'purchase_tax_rate' => '0', 'vat_treatment' => 'non_recoverable', 'unit_cost' => '50000', 'pricing_value' => '50']));
        $this->assertSame('50000.00000000', $own->effective_unit_minor);
        $this->assertSame('110000', $own->suggested_price_minor);
        $this->assertNull($own->supplier_name);
        $external = app(CostService::class)->create($this->admin, $this->data(['catalog_item_id' => $service->id, 'source' => 'external', 'unit_cost' => '55000', 'pricing_value' => '50']));
        $this->assertSame('110000', $external->suggested_price_minor);
        $ownQuote = $this->draft(['catalog_item_id' => $service->id, 'cost_profile_id' => $own->id]);
        $this->assertSame('own', $ownQuote->cost_snapshot['lines'][0]['source']);
        $this->assertSame($own->id, $ownQuote->cost_snapshot['lines'][0]['profile_id']);
        $quote = $this->draft(['catalog_item_id' => $service->id, 'quantity' => '2', 'unit_price' => '110000']);
        $this->assertSame('100000', $quote->cost_snapshot['cost_minor']);
        $this->assertSame('100000', $quote->cost_snapshot['profit_minor']);
        $this->assertSame('50.00', $quote->cost_snapshot['margin_percent']);
        $this->assertSame('external', $quote->cost_snapshot['lines'][0]['source']);
    }

    public function test_snapshot_discount_manual_cost_unknown_zero_and_immutable_pdf(): void
    {
        $profile = app(CostService::class)->create($this->admin, $this->data());
        $quote = $this->draft();
        $this->assertSame('45455', $quote->cost_snapshot['profit_minor']);
        $this->assertSame('9.09', $quote->cost_snapshot['margin_percent']);
        $this->assertArrayNotHasKey('cost_snapshot', $quote->toArray());
        $page = Livewire::test(EditQuote::class, ['record' => $quote->id]);
        $this->assertSame($profile->id, (int) array_values($page->get('data.lines'))[0]['cost_profile_id']);
        app(CostService::class)->create($this->admin, $this->data(['unit_cost' => '800000']));
        $lines = $quote->lines;
        $lines[0]['cost_profile_id'] = $profile->id;
        $quote = app(QuoteService::class)->saveDraft($this->admin, ['customer_id' => $quote->customer_id, 'subject' => $quote->subject, 'currency' => $quote->currency, 'valid_until' => $quote->valid_until->toDateString(), 'lines' => $lines], $quote);
        $this->assertSame('45455', $quote->cost_snapshot['profit_minor']);
        $quote = app(QuoteService::class)->issue($this->admin, $quote);
        $bytes = Storage::disk('local')->get($quote->pdf_path);
        $commercialHtml = view('pdf.quote', ['quote' => $quote, 'draft' => false])->render();
        $this->assertStringNotContainsString('Distribuidor confidencial', $commercialHtml);
        $this->assertStringNotContainsString('COT-INTERNA-001', $commercialHtml);
        $snapshot = $quote->cost_snapshot;
        app(CostService::class)->create($this->admin, $this->data(['unit_cost' => '900000']));
        $this->assertSame($snapshot, $quote->fresh()->cost_snapshot);
        $this->assertSame($bytes, Storage::disk('local')->get($quote->pdf_path));
        $loss = $this->draft(['discount' => '30']);
        $this->assertTrue(bccomp($loss->cost_snapshot['profit_minor'], '0') < 0);
        $manual = $this->draft(['catalog_item_id' => null, 'internal_unit_cost' => '100000']);
        $this->assertSame('400000', $manual->cost_snapshot['profit_minor']);
        $this->assertFalse($this->draft(['catalog_item_id' => null])->cost_snapshot['complete']);
        $this->assertTrue($this->draft(['catalog_item_id' => null, 'internal_unit_cost' => '0'])->cost_snapshot['complete']);
    }

    public function test_foreign_profile_rejected_and_expired_cost_flagged(): void
    {
        $profile = app(CostService::class)->create($this->admin, $this->data());
        try {
            $this->draft(['catalog_item_id' => null, 'cost_profile_id' => $profile->id]);
            $this->fail('Foreign profile accepted');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $this->travel(6)->days();
        $this->assertSame(1, $this->draft()->cost_snapshot['stale_lines']);
        $this->assertSame('555556', $this->item->fresh()->price_minor);
    }

    public function test_cost_form_and_list_persist_server_values(): void
    {
        Livewire::test(CreateCostProfile::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $profile = CostProfile::firstOrFail();
        $this->assertSame('555556', $profile->suggested_price_minor);
        Livewire::test(ListCostProfiles::class)->assertCanSeeTableRecords([$profile]);
        $this->get('/app/cost-profiles')->assertOk();
        $this->get('/app/cost-profiles/create')->assertOk();
    }

    public function test_reader_cannot_access_or_receive_cost_data_and_dashboard_coverage_is_honest(): void
    {
        app(CostService::class)->create($this->admin, $this->data());
        $quote = app(QuoteService::class)->issue($this->admin, $this->draft());
        app(QuoteService::class)->decide($this->admin, $quote, 'accepted', 'Aceptación por correo del cliente.');
        $old = app(QuoteService::class)->issue($this->admin, $this->draft(['catalog_item_id' => null]));
        app(QuoteService::class)->decide($this->admin, $old, 'accepted', 'Aceptación por correo del cliente.');
        $report = app(CommercialDashboard::class)->report($this->admin, 'PYG', 'month');
        $this->assertSame(1, $report['financials']['covered']);
        $this->assertSame(2, $report['financials']['accepted']);
        $this->assertSame('45455', $report['financials']['profit_minor']);
        Livewire::test(Dashboard::class)->assertSee('Utilidad bruta estimada de aprobados');
        $reader = User::create(['name' => 'Reader', 'email' => 'reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->actingAs($reader);
        $this->get('/app/cost-profiles')->assertForbidden();
        $this->get('/app/cost-profiles/create')->assertForbidden();
        $html = $this->get('/app/quotes/'.$quote->id)->assertOk()->getContent();
        foreach (['Rentabilidad interna · estimación', 'Distribuidor confidencial', 'COT-INTERNA-001', 'unit_net_minor', 'cost_snapshot'] as $private) {
            $this->assertStringNotContainsString($private, $html);
        }
        $this->assertNull(app(CommercialDashboard::class)->report($reader, 'PYG', 'month')['financials']);
        $this->expectException(AuthorizationException::class);
        app(CostService::class)->create($reader, $this->data());
    }
}
