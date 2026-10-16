<?php

namespace App\Modules\Access\Services;

use App\Models\User;
use App\Modules\Access\Models\AccessRole;
use App\Modules\Core\Models\AuditEvent;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccessService
{
    public static function authorize(User $actor, string $permission): void
    {
        if (! $actor->fresh()?->allows($permission)) {
            throw new AuthorizationException;
        }
    }

    public static function reauthenticate(User $actor, string $password): void
    {
        $key = 'sensitive:'.$actor->id.':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['current_password' => "Demasiados intentos. Esperá {$seconds} segundos antes de volver a confirmar tu contraseña."]);
        }
        if (! Hash::check($password, $actor->fresh()->password)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['current_password' => 'La contraseña actual no es correcta.']);
        }
        RateLimiter::clear($key);
    }

    public static function audit(?User $actor, string $action, string $type, int $id, array $metadata = []): void
    {
        AuditEvent::create(['actor_id' => $actor?->id, 'action' => $action, 'subject_type' => $type, 'subject_id' => $id, 'metadata' => $metadata + ['result' => str_contains(strtolower($action), 'failed') ? 'failure' : 'success']]);
    }

    public static function revoke(User $user): void
    {
        DB::table('users')->where('id', $user->id)->update(['session_version' => DB::raw('session_version + 1'), 'remember_token' => Str::random(60)]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
    }

    public function save(User $actor, array $data, ?User $user, string $password): User
    {
        $actor = $actor->fresh();
        self::authorize($actor, 'users.manage');
        self::reauthenticate($actor, $password);

        return $this->persist($actor, $data, $user);
    }

    public function createWithPassword(User $actor, array $data): User
    {
        self::authorize($actor, 'users.manage');
        $credentials = Validator::make($data, [
            'new_password' => ['required', 'string', 'min:12', 'max:128', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->letters()->numbers()->symbols()],
        ], [
            'new_password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ])->validate();

        return $this->persist($actor, $data, null, $credentials['new_password']);
    }

    private function persist(User $actor, array $data, ?User $user, ?string $initialPassword = null): User
    {
        $actor = $actor->fresh();
        self::authorize($actor, 'users.manage');
        if (is_string($data['email'] ?? null)) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }
        $data = Validator::make($data, ['name' => 'required|string|max:200', 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($user?->id)], 'role' => 'required|string|exists:access_roles,key', 'status' => 'required|in:active,suspended,deleted', 'reason' => 'required|string|min:10|max:1000'])->validate();

        return DB::transaction(function () use ($actor, $data, $user, $initialPassword) {
            AccessRole::where('key', 'admin')->lockForUpdate()->firstOrFail();
            $target = AccessRole::where('key', $data['role'])->firstOrFail();
            $own = AccessRole::where('key', $actor->fresh()->role)->firstOrFail();
            if ($actor->role !== 'admin' && ($data['role'] === 'admin' || array_diff($target->permissions, $own->permissions))) {
                abort(403);
            }
            $user = $user ? User::withTrashed()->lockForUpdate()->findOrFail($user->id) : new User;
            if ($user->exists && $user->role === 'admin' && ($data['role'] !== 'admin' || $data['status'] !== 'active') && User::where('role', 'admin')->where('is_active', true)->whereNull('suspended_at')->where('id', '!=', $user->id)->count() === 0) {
                throw ValidationException::withMessages(['status' => 'Debe quedar al menos un administrador activo.']);
            }
            if ($actor->role !== 'admin' && $user->role === 'admin') {
                abort(403);
            }
            $new = ! $user->exists;
            $user->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'role' => $data['role'], 'is_active' => $data['status'] === 'active']);
            $user->suspended_at = $data['status'] === 'suspended' ? now() : null;
            $user->deleted_at = $data['status'] === 'deleted' ? now() : null;
            if ($new) {
                $user->password = $initialPassword ?? Str::random(80);
            }
            $user->save();
            self::audit($actor, $new ? 'user.created' : 'user.updated', 'user', $user->id, ['status' => $data['status'], 'role' => $data['role'], 'reason' => $data['reason']]);

            return $user;
        });
    }

    public function saveRole(User $actor, array $data, ?AccessRole $role, string $password): AccessRole
    {
        $actor = $actor->fresh();
        self::authorize($actor, 'roles.manage');
        self::reauthenticate($actor, $password);
        $data = Validator::make($data, ['key' => ['required', 'regex:/^[a-z][a-z0-9_-]{1,49}$/', Rule::unique('access_roles')->ignore($role?->id)], 'name' => 'required|string|max:100', 'permissions' => 'present|array', 'permissions.*' => ['string', Rule::in(array_keys(Permissions::all()))], 'reason' => 'required|string|min:10|max:1000'])->validate();
        foreach ($data['permissions'] as $permission) {
            [$module,$action] = explode('.', $permission, 2);
            if (in_array($action, ['create', 'update', 'delete', 'approve', 'export'], true) && ! in_array($module.'.view', $data['permissions'], true)) {
                throw ValidationException::withMessages(['permissions' => 'Para modificar, aprobar o exportar un módulo también se necesita Consultar.']);
            }
        }

        return DB::transaction(function () use ($actor, $data, $role) {
            AccessRole::where('key', 'admin')->lockForUpdate()->firstOrFail();
            $role = $role ? AccessRole::lockForUpdate()->findOrFail($role->id) : new AccessRole;
            if ($role->key === 'admin' || $data['key'] === 'admin' || ($role->exists && $data['key'] !== $role->key)) {
                throw ValidationException::withMessages(['key' => 'El rol administrador y las claves de roles existentes no se modifican.']);
            }
            $own = AccessRole::where('key', $actor->fresh()->role)->firstOrFail();
            abort_if(array_diff($data['permissions'], $own->permissions), 403);
            $role->fill(collect($data)->only(['key', 'name', 'permissions'])->all())->save();
            foreach (User::where('role', $role->key)->get() as $member) {
                self::revoke($member);
            }
            self::audit($actor, 'role.updated', 'access_role', $role->id, ['permissions' => $role->permissions, 'reason' => $data['reason']]);

            return $role;
        });
    }

    public function invite(User $actor, User $user, string $password): void
    {
        $actor = $actor->fresh();
        self::authorize($actor, 'users.manage');
        self::reauthenticate($actor, $password);
        abort_unless($user->fresh()?->canAccessPanel(Filament::getPanel('operations')), 403);
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['operation' => 'Configurá correo real antes de enviar invitaciones; no se escriben enlaces de acceso en logs.']);
        }
        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['operation' => 'No se pudo enviar el enlace. Esperá antes de reintentar.']);
        }
        self::audit($actor, 'user.invited', 'user', $user->id);
    }

    public function revokeSessions(User $actor, User $user, string $password): void
    {
        $actor = $actor->fresh();
        self::authorize($actor, 'users.manage');
        self::reauthenticate($actor, $password);
        self::revoke($user);
        self::audit($actor, 'user.sessions_revoked', 'user', $user->id);
    }
}
