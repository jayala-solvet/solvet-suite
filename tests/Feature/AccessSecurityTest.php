<?php

namespace Tests\Feature;

use App\Filament\Auth\AppAuthentication;
use App\Filament\Auth\Login;
use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use App\Modules\Access\Models\AccessRole;
use App\Modules\Access\Services\AccessService;
use App\Modules\Access\Services\SecurityAlerts;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Company;
use App\Modules\Costs\Services\QuoteProfitability;
use App\Modules\Customers\Models\Customer;
use App\Modules\Legal\Models\LegalEvent;
use App\Modules\Legal\Services\LegalService;
use App\Modules\Operations\Services\OrderService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteService;
use App\Notifications\AccessLink;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

class AccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $password = 'Security-test-password-2026';

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('operations'));
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => $this->password, 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
        Company::create(['name' => 'Empresa de prueba', 'tax_id' => '1234567-0', 'email' => 'privacidad@example.test', 'address' => 'Domicilio ficticio']);
        DB::table('document_sequences')->insert(['key' => 'quote', 'next_number' => 1]);
    }

    private function user(string $role): User
    {
        return User::create(['name' => $role, 'email' => $role.'@example.test', 'password' => $this->password, 'role' => $role, 'is_active' => true]);
    }

    private function quote(): Quote
    {
        $c = Customer::create(['name' => 'Cliente de prueba']);

        return app(QuoteService::class)->saveDraft($this->admin, ['customer_id' => $c->id, 'subject' => 'Trabajo reservado', 'currency' => 'PYG', 'valid_until' => today()->addDays(5)->toDateString(), 'lines' => [['description' => 'Intervención', 'quantity' => '1', 'unit_price' => '110000', 'tax_rate' => '10', 'discount' => '0']]]);
    }

    private function denied(callable $call): void
    {
        try {
            $call();
            $this->fail('Se permitió una operación prohibida');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }
    }

    private function invalid(callable $call): void
    {
        try {
            $call();
            $this->fail('Se aceptaron datos inválidos');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_new_roles_deny_by_default_and_private_urls_do_not_leak(): void
    {
        $hr = $this->user('hr');
        $this->assertFalse($hr->allows('quotes.view'));
        $this->assertFalse($hr->allows('costs.view'));
        $this->flushSession();
        $this->actingAs($hr);
        foreach (['/app/users', '/app/access-roles', '/app/quotes', '/app/purchases', '/app/catalog-items', '/app/inventory', '/app/workspace', '/app/dashboard', '/app/security-audits'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/app/legal-center')->assertOk();
        $this->get('/app/register')->assertNotFound();
        $this->denied(fn () => app(QuoteService::class)->saveDraft($hr, []));
    }

    public function test_users_are_managed_with_reauthentication_audit_and_no_field_injection(): void
    {
        $data = ['name' => 'Técnico', 'email' => 'new@example.test', 'role' => 'technician', 'status' => 'active', 'reason' => 'Alta autorizada para tareas', 'password' => 'Injected-password', 'session_version' => 999];
        $service = app(AccessService::class);
        $this->invalid(fn () => $service->save($this->admin, $data, null, 'wrong-password'));
        $user = $service->save($this->admin, $data, null, $this->password);
        $this->assertFalse(Hash::check('Injected-password', $user->password));
        $this->assertSame(0, $user->fresh()->session_version);
        $this->assertTrue($user->allows('orders.view-own'));
        $this->assertFalse($user->allows('users.manage'));
        $this->assertDatabaseHas('audit_events', ['action' => 'user.created', 'subject_id' => $user->id]);
        $this->invalid(fn () => $service->save($this->admin, ['name' => 'Admin', 'email' => $this->admin->email, 'role' => 'reader', 'status' => 'active', 'reason' => 'Cambio no permitido último admin'], $this->admin, $this->password));
        $reader = $this->user('reader');
        $this->denied(fn () => $service->save($reader, $data, null, $this->password));
    }

    public function test_suspend_reactivate_soft_delete_and_password_change_revoke_sessions(): void
    {
        $user = $this->user('commercial');
        DB::table('sessions')->insert(['id' => 'sample-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);
        $service = app(AccessService::class);
        $data = ['name' => $user->name, 'email' => $user->email, 'role' => 'commercial', 'status' => 'suspended', 'reason' => 'Suspensión de cuenta de prueba'];
        $service->save($this->admin, $data, $user, $this->password);
        $this->assertDatabaseMissing('sessions', ['id' => 'sample-session']);
        $this->assertGreaterThan(0, $user->fresh()->session_version);
        $this->flushSession();
        $this->actingAs($user);
        $this->get('/app/quotes')->assertForbidden();
        $this->actingAs($this->admin);
        $data['status'] = 'active';
        $service->save($this->admin, $data, $user, $this->password);
        $this->assertTrue($user->fresh()->is_active);
        $version = $user->fresh()->session_version;
        $user->refresh()->update(['password' => 'Changed-test-password']);
        $this->assertGreaterThan($version, $user->fresh()->session_version);
        $data['status'] = 'deleted';
        $service->save($this->admin, $data, $user, $this->password);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $data['status'] = 'active';
        $service->save($this->admin, $data, $user, $this->password);
        $this->assertNotNull(User::find($user->id));
    }

    public function test_role_changes_revoke_sessions_and_cannot_escalate_or_change_admin(): void
    {
        $service = app(AccessService::class);
        $role = $service->saveRole($this->admin, ['key' => 'limited', 'name' => 'Limitado', 'permissions' => ['catalog.view'], 'reason' => 'Rol mínimo de prueba'], null, $this->password);
        $user = $this->user('limited');
        $old = $user->session_version;
        $service->saveRole($this->admin, ['key' => 'limited', 'name' => 'Limitado', 'permissions' => ['catalog.view', 'inventory.view'], 'reason' => 'Habilitar consulta inventario'], $role, $this->password);
        $this->assertGreaterThan($old, $user->fresh()->session_version);
        $this->invalid(fn () => $service->saveRole($this->admin, ['key' => 'admin', 'name' => 'Cambiar admin', 'permissions' => [], 'reason' => 'Intento sobre rol protegido'], AccessRole::where('key', 'admin')->first(), $this->password));
        $this->invalid(fn () => $service->saveRole($this->admin, ['key' => 'bad', 'name' => 'Inválido', 'permissions' => ['invented.permission'], 'reason' => 'Intento de permiso inventado'], null, $this->password));
    }

    public function test_granular_service_permissions_do_not_imply_approval_or_cost_visibility(): void
    {
        AccessRole::create(['key' => 'drafts', 'name' => 'Solo borradores', 'permissions' => ['quotes.view', 'quotes.create', 'customers.view', 'catalog.view']]);
        $actor = $this->user('drafts');
        $customer = Customer::create(['name' => 'Cliente']);
        $quote = app(QuoteService::class)->saveDraft($actor, ['customer_id' => $customer->id, 'subject' => 'Borrador limitado', 'currency' => 'PYG', 'valid_until' => today()->addDay()->toDateString(), 'lines' => [['description' => 'Manual', 'quantity' => '1', 'unit_price' => '110000', 'tax_rate' => '10', 'discount' => '0', 'internal_unit_cost' => '1']]]);
        $this->assertNull($quote->cost_snapshot['lines'][0]['cost_minor']);
        $this->denied(fn () => app(QuoteService::class)->issue($actor, $quote));
        $this->denied(fn () => app(QuoteProfitability::class)->preview($actor, $quote->lines, 'PYG'));
    }

    public function test_technician_sees_only_assigned_orders_and_no_commercial_amounts(): void
    {
        $quote = $this->quote();
        $quote->update(['status' => 'accepted']);
        $a = app(OrderService::class)->fromQuote($this->admin, $quote);
        $second = $this->quote();
        $second->update(['status' => 'accepted']);
        $b = app(OrderService::class)->fromQuote($this->admin, $second);
        $tech = $this->user('technician');
        app(OrderService::class)->assign($this->admin, $a, $tech->id);
        $this->flushSession();
        $this->actingAs($tech);
        $this->get('/app/sales-orders')->assertOk()->assertSee('Trabajo reservado')->assertDontSee('110.000');
        $this->get('/app/sales-orders/'.$a->id)->assertOk()->assertDontSee('Resultado operativo')->assertDontSee('110.000');
        $this->get('/app/sales-orders/'.$b->id)->assertNotFound();
        $this->get('/quotes/'.$quote->id.'/pdf')->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(OrderService::class)->assign($tech, $b, $tech->id);
    }

    public function test_mfa_required_for_admin_and_encrypted_hidden_from_serialization(): void
    {
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $this->admin->getRawOriginal('app_authentication_secret'));
        $this->assertArrayNotHasKey('app_authentication_secret', $this->admin->toArray());
        $this->admin->app_authentication_secret = null;
        $this->admin->save();
        $this->withSession(['security.version' => $this->admin->session_version]);
        $this->get('/app/dashboard')->assertRedirect();
        $this->assertTrue(Filament::getPanel('operations')->isMultiFactorAuthenticationRequired());
        $this->get('/app/multi-factor-authentication/set-up')->assertOk();
    }

    public function test_login_requires_totp_after_valid_password(): void
    {
        auth()->logout();
        $this->flushSession();
        $component = Livewire::test(Login::class)->fillForm(['email' => $this->admin->email, 'password' => $this->password])->call('authenticate');
        $this->assertGuest();
        $this->assertNotNull($component->get('userUndertakingMultiFactorAuthentication'));
        $code = app(Google2FA::class)->getCurrentOtp('JBSWY3DPEHPK3PXP');
        $component->set('data.multiFactor.app.code', $code)->call('authenticate');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_recovery_tokens_are_single_use_expiring_and_account_response_generic(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);
        app(AccessService::class)->invite($this->admin, $this->admin, $this->password);
        Notification::assertSentTo($this->admin, AccessLink::class);
        $token = Notification::sent($this->admin, AccessLink::class)->first()->token;
        $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $this->admin->email)->value('token'));
        $credentials = ['email' => $this->admin->email, 'token' => $token, 'password' => 'New-strong-password-2026', 'password_confirmation' => 'New-strong-password-2026'];
        $this->assertSame(Password::PASSWORD_RESET, Password::reset($credentials, fn ($u, $p) => $u->update(['password' => $p])));
        $this->assertSame(Password::INVALID_TOKEN, Password::reset($credentials, fn () => null));
        $token = Password::createToken($this->admin);
        $this->travel(61)->minutes();
        $credentials['token'] = $token;
        $this->assertSame(Password::INVALID_TOKEN, Password::reset($credentials, fn () => null));
        $this->travelBack();
        auth()->logout();
        $this->flushSession();
        foreach (['absent@example.test', $this->admin->email] as $email) {
            Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $email])->call('request')->assertNotified('Si la cuenta está habilitada, recibirás un enlace para recuperar el acceso.');
        }
    }

    public function test_private_responses_cache_and_expired_security_session(): void
    {
        $this->get('/app/quotes')->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->withSession(['security.started_at' => time() - 9 * 3600])->get('/app/quotes')->assertRedirect('/app/login');
    }

    public function test_legal_publication_requires_real_data_and_preserves_versions_and_events(): void
    {
        $service = app(LegalService::class);
        $doc = $service->draft($this->admin, ['kind' => 'terms', 'title' => 'Condiciones internas', 'version' => '1', 'body' => LegalService::template('terms')], $this->password);
        $this->get('/legal/documents/'.$doc->id)->assertNotFound();
        $this->invalid(fn () => $service->publish($this->admin, $doc, 'Sin revisión jurídica', false, $this->password));
        $service->publish($this->admin, $doc, 'Revisión ficticia de aceptación QA', true, $this->password);
        $this->get('/legal/documents/'.$doc->id)->assertOk()->assertSee('Condiciones internas');
        $this->get('/app/quotes')->assertRedirect('/app/legal-center');
        $service->record($this->admin, $doc->id, 'accepted');
        $service->record($this->admin, $doc->id, 'accepted');
        $this->assertSame(1, LegalEvent::count());
        $this->get('/app/quotes')->assertOk();
        $this->assertSame($doc->sha256, LegalEvent::first()->document_sha256);
        $this->expectException(\LogicException::class);
        $doc->refresh()->update(['body' => 'modificado']);
    }

    public function test_notice_is_not_consent_optional_withdrawal_and_drafts_cannot_publish(): void
    {
        $service = app(LegalService::class);
        $privacy = $service->draft($this->admin, ['kind' => 'privacy', 'title' => 'Privacidad', 'version' => '1', 'body' => LegalService::template('privacy')], $this->password);
        $this->invalid(fn () => $service->publish($this->admin, $privacy, 'Revisión de borrador incompleto', true, $this->password));
        $optional = $service->draft($this->admin, ['kind' => 'optional', 'title' => 'Finalidad opcional QA', 'version' => '1', 'body' => str_repeat('Descripción ficticia de finalidad opcional. ', 5)], $this->password);
        $service->publish($this->admin, $optional, 'Revisión ficticia específica QA', true, $this->password);
        $service->record($this->admin, $optional->id, 'consented');
        $service->record($this->admin, $optional->id, 'withdrawn');
        $this->assertSame(['consented', 'withdrawn'], LegalEvent::orderBy('id')->pluck('action')->all());
        $this->expectException(\LogicException::class);
        LegalEvent::first()->update(['action' => 'changed']);
    }

    public function test_security_mail_queue_is_encrypted_and_alerts_are_sanitized(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'smtp']);
        $token = Password::createToken($this->admin);
        $this->admin->sendPasswordResetNotification($token);
        $payload = DB::table('jobs')->value('payload');
        $this->assertNotNull($payload);
        $this->assertStringNotContainsString($token, $payload);
        $command = json_decode($payload, true)['data']['command'];
        $this->assertNotEmpty(decrypt($command));
        for ($i = 0; $i < 11; $i++) {
            app(SecurityAlerts::class)->failed();
        }
        $this->assertSame(1, AuditEvent::where('action', 'security.alert.login_failures')->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->admin->id]);
    }

    public function test_native_user_form_persists_through_authorized_service(): void
    {
        Livewire::test(CreateUser::class)->fillForm([
            'name' => 'Nuevo técnico', 'email' => 'form-tech@example.test', 'role' => 'technician', 'status' => 'active', 'reason' => 'Alta de prueba desde interfaz', 'current_password' => $this->password,
        ])->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('users', ['email' => 'form-tech@example.test', 'role' => 'technician', 'is_active' => true]);
    }

    public function test_argon2id_verifies_legacy_bcrypt_hashes(): void
    {
        $legacy = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 4]);
        $argon = Hash::driver('argon2id');
        $this->assertTrue($argon->check($this->password, $legacy));
        $new = $argon->make($this->password);
        $this->assertSame('argon2id', password_get_info($new)['algoName']);
        $this->assertTrue($argon->check($this->password, $new));
    }

    public function test_stale_actor_cannot_keep_permissions_after_revocation(): void
    {
        $actor = $this->user('commercial');
        $this->assertTrue($actor->allows('quotes.create'));
        User::whereKey($actor->id)->update(['role' => 'hr']);
        $this->assertFalse($actor->allows('quotes.create'));
        $this->denied(fn () => app(QuoteService::class)->saveDraft($actor, []));
    }

    public function test_proxy_headers_only_affect_requests_from_explicit_proxy(): void
    {
        $middleware = new TrustProxies;
        TrustProxies::at(['10.20.30.40']);
        TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
        try {
            foreach (['198.51.100.12' => false, '10.20.30.40' => true] as $remote => $trusted) {
                $request = Request::create('http://solvet.example.test/', 'GET', [], [], [], ['REMOTE_ADDR' => $remote, 'HTTP_X_FORWARDED_FOR' => '203.0.113.7', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_HOST' => 'attacker.example']);
                $middleware->handle($request, fn () => response('ok'));
                $this->assertSame($trusted, $request->isSecure());
                $this->assertSame($trusted ? '203.0.113.7' : $remote, $request->ip());
                $this->assertSame('solvet.example.test', $request->getHost());
            }
        } finally {
            TrustProxies::flushState();
            Request::setTrustedProxies([], 0);
        }
    }

    public function test_unknown_host_is_rejected_when_explicit_hosts_are_configured(): void
    {
        $old = $this->app['env'];
        $this->app['env'] = 'production';
        TrustHosts::at(['^solvet\\.example\\.test$'], false);
        try {
            $request = Request::create('https://attacker.example/');
            $middleware = new TrustHosts($this->app);
            $this->expectException(SuspiciousOperationException::class);
            $middleware->handle($request, function ($req) {
                $req->getHost();

                return response('bad');
            });
        } finally {
            $this->app['env'] = $old;
            TrustHosts::flushState();
            Request::setTrustedHosts([]);
        }
    }

    public function test_csrf_middleware_rejects_post_without_session_token(): void
    {
        $old = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $request = Request::create('/private-action', 'POST');
            $request->setLaravelSession(app('session')->driver());
            $middleware = new VerifyCsrfToken($this->app, $this->app['encrypter']);
            $this->expectException(TokenMismatchException::class);
            $middleware->handle($request, fn () => response('bad'));
        } finally {
            $this->app['env'] = $old;
        }
    }

    public function test_published_legal_body_is_escaped_and_mfa_disable_is_not_authorized_for_admin(): void
    {
        $service = app(LegalService::class);
        $doc = $service->draft($this->admin, ['kind' => 'terms', 'title' => 'Prueba de escape', 'version' => 'xss-qa', 'body' => str_repeat('Texto de prueba. ', 10).'<script>alert("qa-xss")</script>'], $this->password);
        $service->publish($this->admin, $doc, 'Revisión ficticia de escape de contenido', true, $this->password);
        $this->get('/legal/documents/'.$doc->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("qa-xss")</script>', false);
        $actions = app(AppAuthentication::class)->getActions();
        $disable = collect($actions)->first(fn ($a) => $a->getName() === 'disableAppAuthentication');
        $this->assertFalse($disable->isAuthorized());
    }
}
