<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseResource\Pages\CreatePurchase;
use App\Filament\Resources\PurchaseResource\Pages\ViewPurchase;
use App\Filament\Resources\QuoteResource\Pages\EditQuote;
use App\Filament\Resources\SalesOrderResource\Pages\ViewSalesOrder;
use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Core\Models\Company;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Costs\Services\CostService;
use App\Modules\Customers\Models\Customer;
use App\Modules\Operations\Models\ExecutionEntry;
use App\Modules\Operations\Models\Purchase;
use App\Modules\Operations\Models\StockBalance;
use App\Modules\Operations\Models\StockMovement;
use App\Modules\Operations\Services\InventoryService;
use App\Modules\Operations\Services\OrderService;
use App\Modules\Operations\Services\PurchaseService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Admin', 'email' => 'ops@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
        $this->customer = Customer::create(['name' => 'Cliente de operaciones']);
        Company::create(['name' => 'SOLVET-IT']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 1]);
    }

    private function item(string $name = 'Equipo', array $data = []): CatalogItem
    {
        return app(CatalogService::class)->save($this->admin, $data + ['name' => $name, 'kind' => 'product', 'unit' => 'unit', 'can_buy' => true, 'can_sell' => true, 'track_stock' => true, 'currency' => 'PYG', 'unit_price' => '660000', 'tax_rate' => '10', 'is_active' => true]);
    }

    private function cost(CatalogItem $item, string $amount, array $overrides = [])
    {
        return app(CostService::class)->create($this->admin, $overrides + ['catalog_item_id' => $item->id, 'source' => 'purchase', 'supplier_name' => 'Jorge Marecos', 'supplier_tax_id' => '1234567-0', 'reference' => 'Cotización interna', 'unit_cost' => $amount, 'extra_cost' => '0', 'purchase_tax_rate' => '10', 'vat_treatment' => 'recoverable', 'pricing_mode' => 'manual', 'pricing_value' => '110000', 'cost_date' => today()->toDateString(), 'apply_price' => false]);
    }

    private function quote(array $lines): Quote
    {
        return app(QuoteService::class)->saveDraft($this->admin, ['customer_id' => $this->customer->id, 'subject' => 'Operación de prueba', 'currency' => 'PYG', 'valid_until' => today()->addDays(15)->toDateString(), 'lines' => $lines]);
    }

    private function accepted(array $lines): Quote
    {
        $quote = app(QuoteService::class)->issue($this->admin, $this->quote($lines));

        return app(QuoteService::class)->decide($this->admin, $quote, 'accepted', 'Aceptación registrada para prueba.');
    }

    private function line(CatalogItem $item, string $quantity = '1', string $price = '660000', ?int $profile = null): array
    {
        return ['catalog_item_id' => $item->id, 'cost_profile_id' => $profile, 'description' => $item->name, 'quantity' => $quantity, 'unit_price' => $price, 'discount' => '0', 'tax_rate' => '10'];
    }

    private function purchaseData(array $lines, array $extra = []): array
    {
        return $extra + ['supplier_name' => 'Jorge Marecos', 'supplier_tax_id' => '1234567-0', 'reference' => 'FACT-001', 'currency' => 'PYG', 'purchase_date' => today()->toDateString(), 'freight_amount' => '0', 'freight_tax_rate' => '10', 'freight_vat_treatment' => 'recoverable', 'freight_method' => 'quantity', 'lines' => $lines];
    }

    private function buyLine(CatalogItem $item, string $quantity, string $cost, ?int $orderLine = null, array $extra = []): array
    {
        return $extra + ['catalog_item_id' => $item->id, 'quantity' => $quantity, 'unit_cost' => $cost, 'tax_rate' => '10', 'vat_treatment' => 'recoverable', 'order_line_id' => $orderLine];
    }

    private function receive(array $lines, array $extra = []): Purchase
    {
        $p = app(PurchaseService::class)->save($this->admin, $this->purchaseData($lines, $extra));
        app(PurchaseService::class)->confirm($this->admin, $p);

        return app(PurchaseService::class)->receive($this->admin, $p);
    }

    private function rejected(callable $call): void
    {
        try {
            $call();
            $this->fail('Invalid operation accepted');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_case_a_six_hours_purchased_from_jorge_no_inventory(): void
    {
        $n1 = $this->item('Soporte técnico N1', ['kind' => 'service', 'unit' => 'hour', 'track_stock' => false, 'unit_price' => '110000']);
        $profile = $this->cost($n1, '83000', ['source' => 'external']);
        $quote = $this->accepted([$this->line($n1, '6', '110000', $profile->id)]);
        $this->assertSame('660000', $quote->total_minor);
        $this->assertSame('147273', $quote->cost_snapshot['profit_minor']);
        $this->assertSame('24.54545', $quote->cost_snapshot['margin_percent_precise']);
        $order = app(OrderService::class)->fromQuote($this->admin, $quote);
        $line = $order->lines()->firstOrFail();
        $this->assertSame($order->id, app(OrderService::class)->fromQuote($this->admin, $quote)->id);
        $purchase = $this->receive([$this->buyLine($n1, '6', '83000', $line->id)]);
        $this->assertSame('498000', $purchase->total_minor);
        app(PurchaseService::class)->receive($this->admin, $purchase);
        $this->assertSame(1, ExecutionEntry::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, StockBalance::count());
        $this->rejected(fn () => app(OrderService::class)->recordOwn($this->admin, $line, '6', '65000', 'No duplicar la intervención', Str::uuid()->toString()));
        app(OrderService::class)->complete($this->admin, $order);
        $result = app(OrderService::class)->result($this->admin, $order->fresh());
        $this->assertTrue($result['complete']);
        $this->assertSame('147273', CostCalculator::round($result['profit_minor']));
        $this->assertSame('24.54545', $result['margin_percent']);
    }

    public function test_case_b_grouped_equipment_freight_own_configuration_and_idempotence(): void
    {
        $equipment = $this->item();
        $n1 = $this->item('Configuración N1', ['kind' => 'service', 'unit' => 'hour', 'track_stock' => false]);
        $equipmentCost = $this->cost($equipment, '500000', ['extra_cost' => '20000', 'extra_tax_rate' => '10', 'extra_vat_treatment' => 'recoverable', 'pricing_value' => '660000']);
        $own = $this->cost($n1, '65000', ['source' => 'own', 'purchase_tax_rate' => '0', 'vat_treatment' => 'non_recoverable']);
        $raw = ['description' => 'Equipo instalado y configurado', 'quantity' => '1', 'unit_price' => '660000', 'discount' => '0', 'tax_rate' => '10', 'components' => [
            ['catalog_item_id' => $equipment->id, 'description' => 'Equipo', 'quantity' => '1', 'cost_profile_id' => $equipmentCost->id], ['catalog_item_id' => $n1->id, 'description' => 'Configuración propia', 'quantity' => '1', 'cost_profile_id' => $own->id],
        ]];
        $draft = $this->quote([$raw]);
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, StockBalance::count());
        $this->assertSame('537727', $draft->cost_snapshot['cost_minor']);
        $this->assertSame('62273', $draft->cost_snapshot['profit_minor']);
        $this->assertCount(1, $draft->lines);
        $this->assertArrayNotHasKey('components', $draft->lines[0]);
        $quote = app(QuoteService::class)->issue($this->admin, $draft);
        $quote = app(QuoteService::class)->decide($this->admin, $quote, 'accepted', 'Aceptación del equipo configurado.');
        $bytes = Storage::disk('local')->get($quote->pdf_path);
        $snapshot = $quote->cost_snapshot;
        $order = app(OrderService::class)->fromQuote($this->admin, $quote);
        $goods = $order->lines()->where('track_stock', true)->firstOrFail();
        $service = $order->lines()->where('kind', 'service')->firstOrFail();
        $purchase = $this->receive([$this->buyLine($equipment, '1', '500000', $goods->id)], ['freight_amount' => '20000']);
        app(PurchaseService::class)->receive($this->admin, $purchase);
        $this->assertSame('472727', CostCalculator::round($equipment->fresh()->stock->value_minor));
        $this->assertSame('1.000', $equipment->fresh()->stock->quantity);
        app(OrderService::class)->reserve($this->admin, $order);
        app(OrderService::class)->reserve($this->admin, $order);
        $this->assertSame('1.000', $equipment->fresh()->stock->reserved);
        $token = Str::uuid()->toString();
        app(OrderService::class)->deliver($this->admin, $order, $token);
        app(OrderService::class)->deliver($this->admin, $order, $token);
        $token = Str::uuid()->toString();
        $entry = app(OrderService::class)->recordOwn($this->admin, $service, '1', '65000', 'Configuración propia realizada', $token);
        $this->assertSame($entry->id, app(OrderService::class)->recordOwn($this->admin, $service, '1', '65000', 'Configuración propia realizada', $token)->id);
        app(OrderService::class)->complete($this->admin, $order);
        $result = app(OrderService::class)->result($this->admin, $order->fresh());
        $this->assertSame('537727', CostCalculator::round($result['cost_minor']));
        $this->assertSame('62273', CostCalculator::round($result['profit_minor']));
        $this->assertSame('0.000', $equipment->fresh()->stock->quantity);
        $this->assertSame('0.000', $equipment->fresh()->stock->reserved);
        $this->assertSame(2, StockMovement::count());
        $this->cost($equipment, '900000');
        $this->assertSame($snapshot, $quote->fresh()->cost_snapshot);
        $this->assertSame($bytes, Storage::disk('local')->get($quote->pdf_path));
        $html = view('pdf.quote', ['quote' => $quote, 'draft' => false])->render();
        $this->assertStringContainsString('Equipo instalado y configurado', $html);
        $this->assertStringNotContainsString('Configuración propia', $html);
        $this->assertStringNotContainsString('Jorge Marecos', $html);
    }

    public function test_weighted_average_reservations_and_returns_preserve_outgoing_cost(): void
    {
        $item = $this->item();
        $first = $this->receive([$this->buyLine($item, '2', '100', null, ['tax_rate' => '0', 'vat_treatment' => 'non_recoverable'])]);
        $this->receive([$this->buyLine($item, '2', '200', null, ['tax_rate' => '0', 'vat_treatment' => 'non_recoverable'])]);
        $this->assertSame('150.00000000', $item->fresh()->stock->average());
        $quote = $this->accepted([$this->line($item, '1', '1100')]);
        $order = app(OrderService::class)->fromQuote($this->admin, $quote);
        app(OrderService::class)->reserve($this->admin, $order);
        app(OrderService::class)->deliver($this->admin, $order, Str::uuid()->toString());
        $out = StockMovement::where('kind', 'delivery')->firstOrFail();
        $this->assertSame('-150.00000000', $out->value_minor);
        $this->receive([$this->buyLine($item, '1', '1000', null, ['tax_rate' => '0', 'vat_treatment' => 'non_recoverable'])]);
        $this->assertSame('-150.00000000', $out->fresh()->value_minor);
        $token = Str::uuid()->toString();
        $returned = app(InventoryService::class)->returnMovement($this->admin, $out, '1', 'Devolución del cliente documentada', $token);
        $this->assertSame('150.00000000', $returned->value_minor);
        $this->assertSame($returned->id, app(InventoryService::class)->returnMovement($this->admin, $out, '1', 'Devolución del cliente documentada', $token)->id);
        $this->assertSame('0.000', $order->lines()->first()->done_quantity);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->rejected(fn () => app(InventoryService::class)->returnMovement($this->admin, $out, '1', 'Intento de devolución duplicada', Str::uuid()->toString()));
        $original = StockMovement::where('purchase_id', $first->id)->firstOrFail();
        $before = $item->fresh()->stock->average();
        $supplierReturn = app(InventoryService::class)->returnMovement($this->admin, $original, '1', 'Devolución al proveedor documentada', Str::uuid()->toString());
        $this->assertTrue(bccomp(ltrim($supplierReturn->value_minor, '-'), $before, 8) === 0);
    }

    public function test_partial_reservations_cannot_oversell_or_adjust_reserved_units(): void
    {
        $item = $this->item();
        $this->receive([$this->buyLine($item, '4', '500000')]);
        $one = app(OrderService::class)->fromQuote($this->admin, $this->accepted([$this->line($item, '3')]));
        $two = app(OrderService::class)->fromQuote($this->admin, $this->accepted([$this->line($item, '3')]));
        app(OrderService::class)->reserve($this->admin, $one);
        app(OrderService::class)->reserve($this->admin, $two);
        app(OrderService::class)->reserve($this->admin, $two);
        $this->assertSame('4.000', $item->fresh()->stock->reserved);
        $this->assertSame('1.000', $two->lines()->first()->reserved_quantity);
        $this->assertSame('0.000', $item->fresh()->stock->available());
        $this->rejected(fn () => app(InventoryService::class)->adjust($this->admin, $item, 'out', '1', null, 'No consumir stock reservado', Str::uuid()->toString()));
        $this->rejected(fn () => app(OrderService::class)->complete($this->admin, $one));
        app(OrderService::class)->release($this->admin, $two);
        $this->assertSame('1.000', $item->fresh()->stock->available());
    }

    public function test_freight_allocation_quantity_value_manual_and_no_double_charge(): void
    {
        $a = $this->item('A');
        $b = $this->item('B');
        $lines = [$this->buyLine($a, '2', '100', null, ['tax_rate' => '0']), $this->buyLine($b, '1', '300', null, ['tax_rate' => '0'])];
        foreach (['quantity', 'value', 'manual'] as $method) {
            $input = $lines;
            if ($method === 'manual') {
                $input[0]['freight_share'] = '70';
                $input[1]['freight_share'] = '31';
            }
            $p = app(PurchaseService::class)->save($this->admin, $this->purchaseData($input, ['freight_amount' => '101', 'freight_method' => $method]));
            $sum = '0';
            foreach ($p->lines as $line) {
                $sum = bcadd($sum, $line['freight_net_minor'], 8);
            }
            $this->assertSame('91.81818181', $sum);
            $this->assertSame('601', $p->total_minor);
        }
        $this->rejected(fn () => app(PurchaseService::class)->save($this->admin, $this->purchaseData($lines, ['freight_amount' => '101', 'freight_method' => 'manual'])));
    }

    public function test_initial_balance_documented_adjustment_and_idempotent_key_validation(): void
    {
        $legacy = CatalogItem::create(['name' => 'Anterior', 'kind' => 'product', 'currency' => 'PYG', 'price_minor' => '500000', 'tax_rate' => 10, 'is_active' => true]);
        $this->assertFalse($legacy->fresh()->track_stock);
        $this->assertSame(0, StockBalance::count());
        $item = $this->item();
        $token = Str::uuid()->toString();
        $stock = app(InventoryService::class);
        $move = $stock->adjust($this->admin, $item, 'in', '2', '400000', 'Saldo inicial verificado en acta', $token);
        $this->assertSame($move->id, $stock->adjust($this->admin, $item, 'in', '2', '400000', 'Saldo inicial verificado en acta', $token)->id);
        $this->assertSame('2.000', $item->fresh()->stock->quantity);
        $this->rejected(fn () => $stock->adjust($this->admin, $item, 'in', '3', '400000', 'Saldo inicial verificado en acta', $token));
        $this->rejected(fn () => $stock->adjust($this->admin, $item, 'out', '3', null, 'Salida sin existencias suficientes', Str::uuid()->toString()));
        $this->rejected(fn () => app(CatalogService::class)->save($this->admin, ['name' => $item->name, 'kind' => 'product', 'unit' => 'unit', 'currency' => 'PYG', 'unit_price' => '500000', 'tax_rate' => '10', 'is_active' => true, 'track_stock' => false], $item));
    }

    public function test_new_cost_does_not_publish_price_or_revalue_stock_without_explicit_action(): void
    {
        $item = $this->item();
        $this->receive([$this->buyLine($item, '1', '500000')]);
        $value = $item->fresh()->stock->value_minor;
        $profile = $this->cost($item, '700000', ['pricing_value' => '900000']);
        $this->assertSame('660000', $item->fresh()->price_minor);
        $this->assertNull($item->fresh()->current_cost_id);
        app(CostService::class)->applyPrice($this->admin, $profile);
        $this->assertSame('900000', $item->fresh()->price_minor);
        $this->assertSame($value, $item->fresh()->stock->value_minor);
    }

    public function test_purchase_livewire_paths_and_reader_restrictions(): void
    {
        $item = $this->item();
        Livewire::test(CreatePurchase::class)->fillForm($this->purchaseData([$this->buyLine($item, '1', '500000')]))->call('create')->assertHasNoFormErrors();
        $p = Purchase::firstOrFail();
        Livewire::test(ViewPurchase::class, ['record' => $p->id])->callAction('confirm')->assertHasNoActionErrors();
        Livewire::test(ViewPurchase::class, ['record' => $p->id])->callAction('receive')->assertHasNoActionErrors();
        $this->get('/app/inventory')->assertOk();
        $this->get('/app/inventory/'.$item->id)->assertOk();
        $this->get('/app/purchases')->assertOk();
        $this->get('/app/sales-orders')->assertOk();
        $order = app(OrderService::class)->fromQuote($this->admin, $this->accepted([$this->line($item)]));
        $this->get('/app/sales-orders/'.$order->id)->assertOk();
        $reader = User::create(['name' => 'Consulta', 'email' => 'ops-reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->flushSession();
        $this->actingAs($reader);
        $this->get('/app/purchases')->assertForbidden();
        $this->get('/app/purchases/'.$p->id)->assertForbidden();
        $this->get('/app/inventory/'.$item->id)->assertOk()->assertDontSee('Valor aplicado');
        $this->get('/app/sales-orders/'.$order->id)->assertOk()->assertDontSee('Resultado operativo del pedido')->assertDontSee('Jorge Marecos');
        Livewire::test(ViewSalesOrder::class, ['record' => $order->id])->assertActionHidden('own')->assertActionHidden('reserve');
        $this->expectException(AuthorizationException::class);
        app(OrderService::class)->reserve($reader, $order);
    }

    public function test_executor_changes_preserve_estimate_void_reopens_and_cancel_releases(): void
    {
        $n1 = $this->item('N1', ['kind' => 'service', 'unit' => 'hour', 'track_stock' => false]);
        $profile = $this->cost($n1, '83000', ['source' => 'external']);
        $quote = $this->accepted([$this->line($n1, '6', '110000', $profile->id)]);
        $order = app(OrderService::class)->fromQuote($this->admin, $quote);
        $line = $order->lines()->firstOrFail();
        $before = $order->estimated_snapshot;
        app(OrderService::class)->chooseExecutor($this->admin, $line, 'own', 'Cambio documentado a trabajo propio.');
        $entry = app(OrderService::class)->recordOwn($this->admin, $line, '6', '65000', 'Seis horas ejecutadas por Solvet.', Str::uuid()->toString());
        app(OrderService::class)->complete($this->admin, $order);
        $result = app(OrderService::class)->result($this->admin, $order->fresh());
        $this->assertSame('210000', CostCalculator::round($result['profit_minor']));
        $this->assertSame($before, $order->fresh()->estimated_snapshot);
        $this->assertSame('210000', CostCalculator::round($result['commercial_lines'][0]['profit_minor']));
        app(OrderService::class)->voidExecution($this->admin, $entry, 'Corrección documentada de la ejecución.');
        app(OrderService::class)->voidExecution($this->admin, $entry, 'Corrección documentada de la ejecución.');
        $this->assertSame('0.000', $line->fresh()->done_quantity);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('0', CostCalculator::round(app(OrderService::class)->result($this->admin, $order->fresh())['cost_minor']));
        app(OrderService::class)->cancel($this->admin, $order, 'Cancelación documentada del trabajo.');
        $this->assertSame('cancelled', $order->fresh()->status);
        $p = app(PurchaseService::class)->save($this->admin, $this->purchaseData([$this->buyLine($n1, '1', '83000')]));
        app(PurchaseService::class)->confirm($this->admin, $p);
        app(PurchaseService::class)->cancel($this->admin, $p, 'Compra anulada antes de recibir.');
        $this->rejected(fn () => app(PurchaseService::class)->receive($this->admin, $p));
        $this->assertSame(0, StockMovement::count());
    }

    public function test_bundle_edit_restore_capabilities_and_visible_action_errors(): void
    {
        $item = $this->item();
        $profile = $this->cost($item, '500000');
        $raw = ['description' => 'Conjunto', 'quantity' => '2', 'unit_price' => '660000', 'discount' => '0', 'tax_rate' => '10', 'components' => [['catalog_item_id' => $item->id, 'description' => 'Equipo interno', 'quantity' => '1', 'cost_profile_id' => $profile->id]]];
        $quote = $this->quote([$raw]);
        $page = Livewire::test(EditQuote::class, ['record' => $quote->id]);
        $state = array_values($page->get('data.lines'))[0];
        $component = array_values($state['components'])[0];
        $this->assertSame($item->id, (int) $component['catalog_item_id']);
        $this->assertSame('1', (string) $component['quantity']);
        $saved = app(QuoteService::class)->saveDraft($this->admin, ['customer_id' => $quote->customer_id, 'subject' => $quote->subject, 'currency' => 'PYG', 'valid_until' => $quote->valid_until->toDateString(), 'lines' => $quote->lines], $quote);
        $this->assertSame('2.000000', $saved->cost_snapshot['lines'][0]['components'][0]['quantity']);
        $raw['components'] = [['catalog_item_id' => $item->id, 'description' => ['unsafe'], 'quantity' => '1']];
        $this->rejected(fn () => $this->quote([$raw]));
        $raw['components'] = ['not-an-array'];
        $this->rejected(fn () => $this->quote([$raw]));
        $unbuyable = $this->item('No comprable', ['can_buy' => false]);
        $this->rejected(fn () => app(PurchaseService::class)->save($this->admin, $this->purchaseData([$this->buyLine($unbuyable, '1', '100')])));
        $unsellable = $this->item('No vendible', ['can_sell' => false]);
        $this->rejected(fn () => $this->quote([$this->line($unsellable)]));
        $this->rejected(fn () => $this->item('Servicio inválido', ['kind' => 'service', 'track_stock' => true]));
        $order = app(OrderService::class)->fromQuote($this->admin, $this->accepted([$this->line($item)]));
        Livewire::test(ViewSalesOrder::class, ['record' => $order->id])->callAction('deliver',data: ['token' => Str::uuid()->toString()])->assertNotified('No se pudo completar la operación');
        $this->assertSame(0,StockMovement::count());
    }
}
