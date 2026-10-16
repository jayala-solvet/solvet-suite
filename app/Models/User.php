<?php

namespace App\Models;

use App\Modules\Access\Models\AccessRole;
use App\Notifications\AccessLink;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'email_verified_at' => 'datetime', 'suspended_at' => 'datetime', 'session_version' => 'integer'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ! $this->suspended_at && ! $this->trashed();
    }

    public function canWrite(): bool
    {
        return $this->allows('quotes.create');
    }

    public function allows(string $permission): bool
    {
        $identity = self::withTrashed()->select(['id', 'role', 'is_active', 'suspended_at', 'deleted_at'])->find($this->id);
        if (! $identity || ! $identity->is_active || $identity->suspended_at || $identity->trashed()) {
            return false;
        }
        $role = AccessRole::where('key', $identity->role)->first();

        return $role && in_array($permission, $role->permissions, true);
    }

    public function sendPasswordResetNotification($token): void
    {
        $notification = new AccessLink($token);
        $notification->url = Filament::getPanel('operations')->getResetPasswordUrl($token, $this);
        $this->notify($notification);
    }
}
