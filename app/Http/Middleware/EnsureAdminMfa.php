<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

class EnsureAdminMfa extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        return config('security.mfa_enabled') && $request->user()?->role === 'admin' ? parent::handle($request, $next) : $next($request);
    }
}
