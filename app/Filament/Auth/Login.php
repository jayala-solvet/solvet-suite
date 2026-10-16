<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Auth\Pages\Login
{
    public function authenticate(): ?LoginResponse
    {
        $key = 'login-account:'.hash_hmac('sha256', mb_strtolower(trim($this->data['email'] ?? '')), config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['data.email' => 'No se pudo completar el acceso. Esperá unos minutos antes de reintentar.']);
        }
        try {
            $response = parent::authenticate();
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, 300);
            throw $exception;
        }
        if (auth()->check()) {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
