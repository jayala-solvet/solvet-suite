<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Services\QuoteService;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_controls_are_available_before_and_after_login(): void
    {
        $panel = Filament::getPanel('operations');
        $this->assertTrue($panel->hasDarkMode());
        $this->assertSame(ThemeMode::Light, $panel->getDefaultThemeMode());
        $user = User::create(['name' => 'Appearance Test', 'email' => 'appearance@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        $login = $this->get('/app/login')->assertOk();
        $workspace = $this->actingAs($user)->get('/app/workspace')->assertOk();
        foreach ([$login, $workspace] as $response) {
            foreach (['claro', 'oscuro', 'sistema'] as $mode) {
                $response->assertSee('aria-label="Modo '.$mode.'"', false);
            }
            $response->assertSee('aria-label="Apariencia"', false);
            $response->assertSee('aria-pressed', false);
            $response->assertSee('solvet-theme.css?v='.substr(hash_file('sha256', public_path('css/solvet.css')), 0, 16), false);
        }
    }

    public function test_history_uses_the_shared_browser_preference_and_theme_surfaces(): void
    {
        $user = User::create(['name' => 'Appearance Test', 'email' => 'history@example.test', 'password' => 'test-password-long', 'role' => 'admin', 'is_active' => true]);
        Company::create(['name' => 'Solvet Test']);
        $customer = Customer::create(['name' => 'Cliente de prueba']);
        $quote = app(QuoteService::class)->saveDraft($user, [
            'customer_id' => $customer->id, 'subject' => 'Historial', 'currency' => 'PYG',
            'valid_until' => today()->addDays(15)->toDateString(),
            'lines' => [['description' => 'Servicio', 'quantity' => '1', 'unit_price' => '10000', 'discount' => '0', 'tax_rate' => '10']],
        ]);
        $this->actingAs($user)->get(route('quotes.history', $quote))->assertOk()
            ->assertSee('filament:theme:mode', false)
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertSee('class="sv-history"', false)
            ->assertDontSee('style="background:#F3F6FA', false);
    }
}
