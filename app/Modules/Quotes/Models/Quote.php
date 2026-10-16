<?php

namespace App\Modules\Quotes\Models;

use App\Modules\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['cost_snapshot'];

    protected function casts(): array
    {
        return ['cost_snapshot' => 'array', 'subtotal_minor' => 'decimal:0', 'discount_minor' => 'decimal:0', 'tax_minor' => 'decimal:0', 'total_minor' => 'decimal:0', 'lines' => 'array', 'snapshot' => 'array', 'issued_at' => 'datetime', 'valid_until' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): string
    {
        return $this->number ? 'PPTO-'.str_pad((string) $this->number, 6, '0', STR_PAD_LEFT) : 'Borrador #'.$this->id;
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Borrador', 'issued' => 'Emitido', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'cancelled' => 'Anulado'][$this->status] ?? $this->status;
    }

    public function paymentLabel(): string
    {
        return match ($this->payment_mode) {
            'cash' => 'Contado',
            'credit' => 'Crédito a '.$this->credit_days.' días',
            default => 'A convenir',
        };
    }

    public function formattedTotal(): string
    {
        return self::money($this->total_minor, $this->currency);
    }

    public static function money(string|int $minor, string $currency): string
    {
        $decimals = $currency === 'USD' ? 2 : 0;
        $parts = explode('.', bcdiv((string) $minor, (string) (10 ** $decimals), $decimals));
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[0]);

        return ($currency === 'PYG' ? '₲ ' : 'US$ ').$integer.($decimals ? ','.str_pad($parts[1] ?? '', 2, '0') : '');
    }
}
