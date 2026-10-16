<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user()?->fresh();
        abort_unless($user && $user->is_active && ! $user->suspended_at && ! $user->trashed(), 403);
        if ($request->hasSession()) {
            $session = $request->session();
            $start = $session->get('security.started_at', time());
            if ((int) $session->get('security.version', 0) !== $user->session_version || time() - (int) $start > config('security.absolute_session_minutes') * 60) {
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                return redirect('/app/login');
            }
            $session->put('security.started_at', $start);
        }
        if ($request->is('quotes*') && $user->role === 'admin' && ! $user->getAppAuthenticationSecret()) {
            return redirect('/app/multi-factor-authentication/set-up');
        }

        return $next($request);
    }
}
