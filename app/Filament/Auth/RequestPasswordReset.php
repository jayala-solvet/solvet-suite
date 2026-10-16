<?php

namespace App\Filament\Auth;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset
{
    public function request(): void
    {
        $data = $this->form->getState();
        $key = 'password-request:'.hash('sha256', mb_strtolower($data['email']).'|'.request()->ip());
        $ip = 'password-ip:'.request()->ip();
        if (! RateLimiter::tooManyAttempts($key, 2) && ! RateLimiter::tooManyAttempts($ip, 10)) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($ip, 300);
            if (! in_array(config('mail.default'), ['log', 'array'], true)) {
                Password::sendResetLink(['email' => mb_strtolower($data['email']), 'is_active' => true, 'suspended_at' => null]);
            }
        }
        Notification::make()->title('Si la cuenta está habilitada, recibirás un enlace para recuperar el acceso.')->success()->send();
        $this->form->fill();
    }
}
