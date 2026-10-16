<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Modules\Customers\Models\Customer;
use App\Modules\Dashboard\Services\CommercialDashboard;
use App\Modules\Quotes\Models\Quote;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CommercialDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $reader;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(12, 0));
        $this->reader = User::create(['name' => 'Consulta', 'email' => 'reader@example.test', 'password' => 'test-password-long', 'role' => 'reader', 'is_active' => true]);
        $this->customer = Customer::create(['name' => 'Cliente actual']);
        $this->actingAs($this->reader);
        Filament::setCurrentPanel(Filament::getPanel('operations'));
    }

    private function quote(array $data = []): Quote
    {
        return Quote::create($data + [
            'customer_id' => $this->customer->id, 'created_by' => $this->reader->id,
            'subject' => 'Propuesta de prueba', 'currency' => 'PYG', 'status' => 'issued',
            'valid_until' => today()->addDays(7), 'issued_at' => now(), 'lines' => [],
            'subtotal_minor' => '100', 'discount_minor' => '0', 'tax_minor' => '9', 'total_minor' => '100',
            'snapshot' => ['customer' => ['name' => 'Cliente al emitir']],
        ]);
    }

    private function report(string $currency = 'PYG', string $period = 'month'): array
    {
        return app(CommercialDashboard::class)->report($this->reader, $currency, $period);
    }

    public function test_cohort_partition_currency_expiry_and_soft_deleted_exclusions(): void
    {
        $accepted = $this->quote(['status' => 'accepted', 'total_minor' => '300']);
        $pending = $this->quote(['valid_until' => today()]);
        $expired = $this->quote(['valid_until' => today()->subDay(), 'total_minor' => '200']);
        $this->quote(['status' => 'rejected', 'total_minor' => '400']);
        $this->quote(['status' => 'cancelled', 'total_minor' => '500']);
        $this->quote(['status' => 'draft', 'issued_at' => null]);
        $this->quote(['currency' => 'USD', 'total_minor' => '999']);
        $this->quote(['issued_at' => now()->subMonth()]);
        $this->quote(['issued_at' => now()->addDay()]);
        $this->quote()->delete();
        $report = $this->report();
        $this->assertSame('1500', $report['total']);
        $this->assertSame(5, $report['count']);
        $this->assertSame('300', $report['buckets']['accepted']['minor']);
        $this->assertSame('100', $report['buckets']['pending']['minor']);
        $this->assertSame('200', $report['buckets']['expired']['minor']);
        $this->assertSame(1, $report['dueSoon']);
        $this->assertSame([$expired->id, $pending->id], $report['followUp']->modelKeys());
        $this->assertSame('999', $this->report('USD')['total']);
        $this->assertSame('1600', $this->report('PYG', 'year')['total']);
        $this->assertSame('1600', $this->report('PYG', 'all')['total']);
        $this->assertSame('1500', $this->report('PYG', 'quarter')['total']);
        $this->assertSame('accepted', $accepted->fresh()->status);
        $this->assertSame('issued', $expired->fresh()->status);
    }

    public function test_exact_large_totals_zero_amounts_and_no_fabricated_profit(): void
    {
        $this->quote(['status' => 'accepted', 'total_minor' => '9007199254740991']);
        $this->quote(['status' => 'accepted', 'total_minor' => '9007199254740991']);
        $this->assertSame('18014398509481982', $this->report()['total']);
        $this->quote(['currency' => 'USD', 'total_minor' => '0']);
        $this->assertSame('0', $this->report('USD')['buckets']['pending']['share']);
        Livewire::test(Dashboard::class)->assertSee('₲ 18.014.398.509.481.982')
            ->assertSee('Rentabilidad interna restringida')->assertSee('no representa ganancia ni cobro')
            ->assertActionHidden('createQuote');
    }

    public function test_empty_filters_links_snapshot_and_follow_up_limit(): void
    {
        Livewire::test(Dashboard::class)->assertSee('El resumen empieza al emitir un presupuesto');
        $quote = $this->quote();
        for ($i = 0; $i < 7; $i++) {
            $this->quote();
        }
        $this->assertCount(6, $this->report()['followUp']);
        Livewire::test(Dashboard::class)->assertSee('Cliente al emitir')->assertDontSee('Cliente actual')
            ->assertSee('/app/quotes/'.$quote->id)->assertSee('Se muestran los primeros 6')
            ->set('currency', 'USD')->assertSee('US$ 0,00')->assertSee('Todavía no hay emisiones')
            ->set('period', 'all')->assertSee('Inicio del historial');
        $this->get('/app/dashboard')->assertOk();
        $this->get('/app/workspace')->assertOk();
        $this->actingAs(User::create(['name' => 'Comercial', 'email' => 'sales@example.test', 'password' => 'test-password-long', 'role' => 'commercial', 'is_active' => true]));
        Livewire::test(Dashboard::class)->assertActionVisible('createQuote');
    }

    public function test_invalid_filters_and_inactive_access_are_rejected(): void
    {
        try {
            $this->report('EUR');
            $this->fail('Unsupported currency accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('currency', $e->errors());
        }
        try {
            $this->report('PYG', 'arbitrary');
            $this->fail('Unsupported period accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('period', $e->errors());
        }
        $this->reader->update(['is_active' => false]);
        $this->get('/app/dashboard')->assertForbidden();
        $this->expectException(HttpException::class);
        $this->report();
    }
}
