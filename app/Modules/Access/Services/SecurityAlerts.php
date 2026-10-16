<?php

namespace App\Modules\Access\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecurityAlerts
{
    public function failed(): void
    {
        $correlation = hash_hmac('sha256', (string) request()->ip(), config('app.key'));
        $key = 'security-failed:'.$correlation;
        RateLimiter::hit($key, 900);
        if (RateLimiter::attempts($key) < 10 || ! RateLimiter::attempt('security-alert:'.$correlation, 1, fn () => true, 900)) {
            return;
        }
        AccessService::audit(null, 'security.alert.login_failures', 'security', 0, ['correlation' => $correlation, 'window_minutes' => 15]);
        Log::warning('security.alert.login_failures', ['correlation' => $correlation]);
        $notification = Notification::make()->title('Intentos de acceso repetidos')->body('Se alcanzó el umbral de fallos de autenticación. Revisá la auditoría y el acceso del origen.')->warning()->toDatabase();
        foreach (User::where('role', 'admin')->where('is_active', true)->get() as $admin) {
            $admin->notifyNow($notification);
        }
    }
}
