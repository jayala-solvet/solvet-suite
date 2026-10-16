<?php

namespace App\Modules\Operations\Services;

use App\Models\User;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Quotes\Services\QuoteCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OperationRules
{
    public static function quantityLabel(string $value): string
    {
        return str_replace('.', ',', rtrim(rtrim(bcadd($value, '0', 3), '0'), '.'));
    }

    public static function authorize(User $actor): void
    {
        Gate::forUser($actor)->authorize('manage-costs');
    }

    public static function fail(string $message): never
    {
        throw ValidationException::withMessages(['operation' => $message]);
    }

    public static function quantity(string $value): string
    {
        Validator::make(['quantity' => $value], ['quantity' => ['required', 'regex:/^\d{1,9}(\.\d{1,3})?$/', 'numeric', 'gt:0']])->validate();

        return bcadd($value, '0', 3);
    }

    public static function token(string $value): void
    {
        Validator::make(['token' => $value], ['token' => 'required|uuid'])->validate();
    }

    public static function money(string $value, string $currency): string
    {
        Validator::make(['currency' => $currency, 'amount' => $value], ['currency' => 'required|in:PYG,USD', 'amount' => ['required', 'regex:'.QuoteCalculator::pricePattern($currency)]])->validate();

        return bcmul($value, $currency === 'USD' ? '100' : '1', 0);
    }

    public static function effective(string $gross, int $rate, string $treatment): string
    {
        Validator::make(compact('rate', 'treatment'), ['rate' => 'required|in:0,5,10', 'treatment' => 'required|in:recoverable,non_recoverable'])->validate();

        return $treatment === 'recoverable' ? bcdiv(bcmul($gross, '100', 12), (string) (100 + $rate), 8) : bcadd($gross, '0', 8);
    }

    public static function audit(User $actor, string $action, string $type, int $id, array $metadata = []): void
    {
        AuditEvent::create(['actor_id' => $actor->id, 'action' => $action, 'subject_type' => $type, 'subject_id' => $id, 'metadata' => $metadata]);
    }

    public static function same(string $saved, string $hash): void
    {
        if (! hash_equals($saved, $hash)) {
            self::fail('La clave de reintento ya se utilizó con otros datos.');
        }
    }
}
