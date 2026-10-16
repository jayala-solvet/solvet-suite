<?php

namespace App\Observers;

use App\Models\User;
use App\Modules\Access\Services\AccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSecurityObserver
{
    public function updating(User $user): void
    {
        if ($user->isDirty(['password', 'email', 'role', 'is_active', 'suspended_at', 'deleted_at', 'app_authentication_secret', 'app_authentication_recovery_codes'])) {
            $user->session_version = (int) $user->getOriginal('session_version') + 1;
            $user->remember_token = Str::random(60);
        }
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged('session_version')) {
            return;
        }
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->whereIn('email', [$user->email, $user->getOriginal('email')])->delete();
        if ($user->wasChanged(['app_authentication_secret', 'app_authentication_recovery_codes']) && auth()->id() === $user->id && request()->hasSession()) {
            request()->session()->put('security.version', $user->session_version);
        }
        AccessService::audit(auth()->user(), 'user.security_changed', 'user', $user->id, ['fields' => array_values(array_intersect(array_keys($user->getChanges()), ['password', 'email', 'role', 'is_active', 'suspended_at', 'deleted_at', 'app_authentication_secret', 'app_authentication_recovery_codes']))]);
    }
}
