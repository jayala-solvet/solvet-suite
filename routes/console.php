<?php

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Modules\Legal\Services\LegalService;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('solvet:install', function () {
    Company::firstOrCreate(['id' => 1], ['name' => 'Solvet-IT', 'email' => null]);
    DB::table('document_sequences')->insertOrIgnore(['key' => 'quote', 'next_number' => 1]);
    $this->info('Empresa y secuencia inicializadas. Completá los datos en Configuración.');
});
Artisan::command('solvet:user {--name=} {--email=} {--role=admin}', function () {
    $name = $this->option('name') ?: $this->ask('Nombre');
    $email = $this->option('email') ?: $this->ask('Correo');
    $role = $this->option('role');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! in_array($role, ['admin', 'commercial', 'reader'], true)) {
        $this->error('Correo o rol inválido.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('El usuario ya existe. No se ha modificado.');

        return 1;
    }
    $password = $this->secret('Contraseña (mínimo 12 caracteres)');
    if (! $password || strlen($password) < 12) {
        $this->error('La contraseña debe tener al menos 12 caracteres.');

        return 1;
    }
    User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role, 'is_active' => true]);
    $this->info('Usuario creado.');
});
Artisan::command('solvet:demo', function () {
    if (! app()->environment('local', 'testing')) {
        $this->error('La demostración solo se carga en local o testing.');

        return 1;
    }
    if (Customer::exists() || Quote::exists()) {
        $this->error('La base debe estar vacía de clientes y presupuestos.');

        return 1;
    }
    $user = User::where('role', 'admin')->firstOrFail();
    $service = app(QuoteService::class);
    foreach ([['Empresa Demo Norte', 'Renovación de equipos', '2300000', 'issued'], ['Empresa Demo Centro', 'Mantenimiento preventivo', '850000', 'draft'], ['Empresa Demo Sur', 'Servicio de respaldo', '1250000', 'accepted']] as [$name,$subject,$price,$status]) {
        $customer = Customer::create(['name' => $name, 'contact' => 'Contacto de demostración', 'email' => 'contacto@example.test']);
        $q = $service->saveDraft($user, ['customer_id' => $customer->id, 'subject' => $subject, 'currency' => 'PYG', 'valid_until' => today()->addDays(15)->toDateString(), 'terms' => 'Datos de demostración. Precios con IVA incluido.', 'lines' => [['description' => $subject, 'quantity' => '1', 'unit_price' => $price, 'discount' => '0', 'tax_rate' => '10']]]);
        if ($status !== 'draft') {
            $q = $service->issue($user, $q);
        }
        if ($status === 'accepted') {
            $service->decide($user, $q, 'accepted', 'Aceptación simulada para demostración; sin efectos comerciales.');
        }
    }
    $this->info('Demostración cargada: tres clientes y tres presupuestos ficticios.');
});

Artisan::command('solvet:security-check {--public}', function () {
    $failures = [];
    $check = function (string $name, bool $ok) use (&$failures) {
        $this->line(($ok ? 'OK: ' : 'PENDIENTE: ').$name);
        if (! $ok) {
            $failures[] = $name;
        }
    };
    $check('APP_KEY configurada', filled(config('app.key')));
    $check('Argon2id disponible', in_array('argon2id', password_algos(), true));
    $check('Cookies HttpOnly', (bool) config('session.http_only'));
    $check('SameSite acotado', in_array(config('session.same_site'), ['lax', 'strict'], true));
    $check('Sesiones de base de datos', config('session.driver') === 'database');
    $check('MFA habilitado', (bool) config('security.mfa_enabled'));
    $check('Administradores con segundo factor', ! User::where('role', 'admin')->where('is_active', true)->whereNull('app_authentication_secret')->exists());
    if ($this->option('public')) {
        $check('Entorno production sin depuración', app()->environment('production') && ! config('app.debug'));
        $check('URL HTTPS y cookie Secure', str_starts_with(config('app.url'), 'https://') && (bool) config('session.secure'));
        $check('Hosts explícitos', ! empty(config('security.trusted_hosts')));
        $check('Proxies explícitos y sin comodín', ! empty(config('security.trusted_proxies')) && ! in_array('*', config('security.trusted_proxies'), true));
        $check('Destinatario de cifrado de respaldos configurado', filled(config('security.backup_age_recipient')));
        $check('Correo real configurado', ! in_array(config('mail.default'), ['log', 'array'], true));
        if (DB::connection()->getDriverName() === 'pgsql') {
            $role = DB::selectOne('SELECT rolsuper, rolcreatedb, rolcreaterole, rolbypassrls FROM pg_roles WHERE rolname = current_user');
            $check('Rol de aplicación sin privilegios administrativos', ! $role->rolsuper && ! $role->rolcreatedb && ! $role->rolcreaterole && ! $role->rolbypassrls);
        } else {
            $check('Motor PostgreSQL del destino', false);
        }
        $kinds = LegalService::current()->pluck('kind')->all();
        foreach (['terms', 'privacy', 'cookies'] as $kind) {
            $check('Versión legal revisada: '.$kind, in_array($kind, $kinds, true));
        }
    }
    $this->warn('Este diagnóstico no prueba firewall, TLS/proxy real, cifrado de disco/backups, restauración, concurrencia o revisión jurídica. Revisar docs/seguridad-validacion.md.');

    return $failures ? 1 : 0;
});
