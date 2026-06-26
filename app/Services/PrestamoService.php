<?php

namespace App\Services;

use App\Models\Prestamo;

class PrestamoService
{
    private const TASAS = [
        'mensual' => 0.20,
        'quincenal' => 0.15,
        'semanal' => 0.05,
    ];

    private const TARIFAS_MULTA = [
        50000 => 2000,
        150000 => 3000,
    ];

    private const TARIFA_MULTA_DEFAULT = 5000;

    public function tasa(Prestamo $prestamo): float
    {
        return self::TASAS[$prestamo->frecuencia] ?? 0;
    }

    public function interesPeriodo(Prestamo $prestamo): int
    {
        if ($prestamo->monto <= 0 || $prestamo->saldo <= 0) {
            return 0;
        }

        $mitad = $prestamo->monto / 2;
        $base = $prestamo->saldo > $mitad ? $prestamo->monto : $mitad;

        return (int) round($base * $this->tasa($prestamo));
    }

    public function multaPorDia(int $monto): int
    {
        foreach (self::TARIFAS_MULTA as $tope => $tarifa) {
            if ($monto <= $tope) {
                return $tarifa;
            }
        }

        return self::TARIFA_MULTA_DEFAULT;
    }

    public function multaAcumulada(Prestamo $prestamo): int
    {
        if ($prestamo->multa_acumulada > 0) {
            return $prestamo->multa_acumulada;
        }

        if ($prestamo->dias_atraso <= 0) {
            return 0;
        }

        return $this->multaPorDia($prestamo->monto) * $prestamo->dias_atraso;
    }

    public function interesesAtrasadosTotal(Prestamo $prestamo): int
    {
        return (int) $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sum('monto');
    }

    public function totalASaldar(Prestamo $prestamo): int
    {
        return $prestamo->saldo + $this->interesesAtrasadosTotal($prestamo) + $this->multaAcumulada($prestamo);
    }

    public function abonado(Prestamo $prestamo): int
    {
        return max(0, $prestamo->monto - $prestamo->saldo);
    }
}
