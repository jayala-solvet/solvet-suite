<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Access\Services\AccessService;
use App\Modules\Access\Services\Permissions;
use App\Modules\Access\Services\SecurityAlerts;
use App\Modules\Core\Models\Company;
use App\Modules\Customers\Models\Customer;
use App\Observers\BusinessAuditObserver;
use App\Observers\UserSecurityObserver;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('write-business', fn (User $user) => $user->canWrite());
        Gate::define('view-costs', fn (User $user) => $user->allows('costs.view'));
        Gate::define('manage-costs', fn (User $user) => $user->allows('costs.create'));
        foreach (array_keys(Permissions::all()) as $permission) {
            Gate::define($permission, fn (User $user) => $user->allows($permission));
        }
        User::observe(UserSecurityObserver::class);
        Customer::observe(BusinessAuditObserver::class);
        Company::observe(BusinessAuditObserver::class);
        Password::defaults(fn () => Password::min(12)->max(128));
        Event::listen(Login::class, function ($event) {
            if (request()->hasSession()) {
                request()->session()->put(['security.version' => $event->user->session_version, 'security.started_at' => time()]);
            }
        });
        foreach ([Login::class, Failed::class, Logout::class] as $event) {
            Event::listen($event, function ($e) {
                AccessService::audit($e->user ?? null, 'auth.'.class_basename($e), 'user', $e->user?->id ?? 0);
            });
        }
        Event::listen(Failed::class, fn () => app(SecurityAlerts::class)->failed());
        $stylesheet = public_path('css/solvet.css');
        FilamentAsset::appVersion(substr(hash_file('sha256', $stylesheet), 0, 16));
        FilamentAsset::register([Css::make('solvet-theme', $stylesheet)]);
    }
}
