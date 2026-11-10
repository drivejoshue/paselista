<?php
declare(strict_types=1);

namespace App\Services\Billing;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class ProrationCalculator
{
    /**
     * Las fechas utilizan intervalo [inicio, fin).
     *
     * Retorna centavos para evitar acumular errores
     * de punto flotante en importes monetarios.
     */
    public function calculate(
        string $periodPrice,
        string $periodStartsOn,
        string $periodEndsExclusive,
        string $activatedOn
    ): int {
        $start = CarbonImmutable::parse($periodStartsOn)->startOfDay();
        $end = CarbonImmutable::parse($periodEndsExclusive)->startOfDay();
        $activation = CarbonImmutable::parse($activatedOn)->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException(
                'El período de facturación no es válido.'
            );
        }

        $priceCents = $this->toCents($periodPrice);

        $totalDays = (int) $start->diffInDays($end);

        $chargeStarts = $activation->lessThan($start)
            ? $start
            : $activation;

        if ($chargeStarts->greaterThanOrEqualTo($end)) {
            return 0;
        }

        $remainingDays = (int) $chargeStarts->diffInDays($end);

        return intdiv(
            $priceCents * $remainingDays + intdiv($totalDays, 2),
            $totalDays
        );
    }

    private function toCents(string $amount): int
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Importe monetario inválido.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        $cents = (int) $whole * 100
            + (int) str_pad($fraction, 2, '0');

        if ($cents < 0 || $cents > 999999999999) {
            throw new InvalidArgumentException(
                'El importe excede el máximo permitido.'
            );
        }

        return $cents;
    }
}
