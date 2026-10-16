<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CommercialDashboard
{
    public const PERIODS = ['month' => 'Este mes', 'quarter' => 'Este trimestre', 'year' => 'Este año', 'all' => 'Todo el historial'];

    public function report(User $actor, string $currency, string $period): array
    {
        abort_unless($actor->is_active && in_array($actor->role, ['admin', 'commercial', 'reader'], true), 403);
        Validator::make(compact('currency', 'period'), [
            'currency' => 'required|in:PYG,USD', 'period' => 'required|in:month,quarter,year,all',
        ])->validate();

        $now = now();
        $today = $now->toDateString();
        $soon = $now->copy()->addDays(7)->toDateString();
        $start = match ($period) {
            'month' => $now->copy()->startOfMonth(),
            'quarter' => $now->copy()->startOfQuarter(),
            'year' => $now->copy()->startOfYear(),
            default => null,
        };
        $query = Quote::query()->where('currency', $currency)->whereNotNull('issued_at')
            ->where('issued_at', '<=', $now)->whereIn('status', ['issued', 'accepted', 'rejected', 'cancelled'])
            ->when($start, fn ($q) => $q->where('issued_at', '>=', $start));

        $buckets = [];
        foreach (['accepted' => 'Aprobados', 'pending' => 'Vigentes sin respuesta', 'expired' => 'Vencidos sin respuesta', 'rejected' => 'Rechazados', 'cancelled' => 'Anulados'] as $key => $label) {
            $buckets[$key] = ['label' => $label, 'minor' => '0', 'count' => 0];
        }
        $total = '0';
        $count = 0;
        $dueSoon = 0;
        $financials = Gate::forUser($actor)->allows('view-costs') ? ['covered' => 0, 'accepted' => 0, 'profit_minor' => '0', 'sales_net_minor' => '0'] : null;
        // Stream canonical minor-unit strings: no SQL/SQLite floating-point SUM or monetary casts to float.
        foreach ((clone $query)->select(array_merge(['id', 'status', 'total_minor', 'valid_until'], $financials !== null ? ['cost_snapshot'] : []))->cursor() as $quote) {
            $date = $quote->valid_until->toDateString();
            if ($financials !== null && $quote->status === 'accepted') {
                $financials['accepted']++;
                $snapshot = $quote->cost_snapshot;
                if (($snapshot['complete'] ?? false) && ($snapshot['currency'] ?? null) === $currency && isset($snapshot['profit_minor'])) {
                    $financials['covered']++;
                    $financials['profit_minor'] = bcadd($financials['profit_minor'], $snapshot['profit_minor'], 0);
                    $financials['sales_net_minor'] = bcadd($financials['sales_net_minor'], $snapshot['sales_net_minor'], 0);
                }
            }
            $key = $quote->status === 'issued' ? ($date < $today ? 'expired' : 'pending') : $quote->status;
            $buckets[$key]['minor'] = bcadd($buckets[$key]['minor'], $quote->total_minor, 0);
            $buckets[$key]['count']++;
            $total = bcadd($total, $quote->total_minor, 0);
            $count++;
            if ($quote->status === 'issued' && $date >= $today && $date <= $soon) {
                $dueSoon++;
            }
        }
        foreach ($buckets as &$bucket) {
            $bucket['share'] = bccomp($total, '0', 0) > 0 ? bcdiv(bcmul($bucket['minor'], '100', 0), $total, 2) : '0';
        }
        unset($bucket);

        $followUp = (clone $query)->with('customer:id,name')->where('status', 'issued')
            ->select(['id', 'customer_id', 'number', 'subject', 'currency', 'status', 'total_minor', 'snapshot', 'valid_until'])
            ->orderBy('valid_until')->orderBy('id')->limit(6)->get();

        if ($financials !== null) {
            $financials['margin_percent'] = bccomp($financials['sales_net_minor'], '0', 0) > 0 ? bcdiv(bcmul($financials['profit_minor'], '100', 2), $financials['sales_net_minor'], 2) : null;
        }

        return compact('financials', 'total', 'count', 'buckets', 'followUp', 'dueSoon', 'today') + [
            'range' => ($start ? $start->format('d/m/Y') : 'Inicio del historial').' — '.$now->format('d/m/Y'),
            'asOf' => $now->format('d/m/Y H:i'),
        ];
    }
}
