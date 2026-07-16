<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;

class PrestamoService
{
    // ── Constantes de negocio (§5.2) ─────────────────────────────────────────
    // Una sola definición; nunca escribir estas tasas en otro lugar del código.
    private const TASAS = [
        'mensual'   => 0.20,
        'quincenal' => 0.10,
        'semanal'   => 0.05,
    ];

    // Tarifa de multa por día según el monto del préstamo (§5.5).
    // Clave = tope inclusivo (<=); por encima del último tope → TARIFA_MULTA_DEFAULT.
    // Tramos: monto < 100k → 2.000/día | 100k–149.999 → 3.000/día | ≥ 150k → 5.000/día
    private const TARIFAS_MULTA = [
        99999  => 2000,
        149999 => 3000,
    ];

    private const TARIFA_MULTA_DEFAULT = 5000;

    // ── Tasa e interés del período ────────────────────────────────────────────

    public function tasa(Prestamo $prestamo): float
    {
        return self::TASAS[$prestamo->frecuencia] ?? 0;
    }

    /**
     * Interés a cobrar en el período actual (§5.3).
     *
     * Si hay un interés parcialmente pagado del período anterior (interes_pendiente > 0),
     * devuelve ese monto restante en vez de recalcular: el cliente todavía debe la diferencia
     * del período que no cerró, y proximo no ha avanzado.
     *
     * Fórmula normal (§5.3):
     *   - Préstamos >= ₡200.000: base = monto original mientras saldo > monto/2;
     *     una vez que baja a la mitad o menos, base = monto/2 (fijo de ahí en adelante).
     *   - Préstamos < ₡200.000: base = saldo real siempre (sin recálculo a la mitad).
     */
    public function interesPeriodo(Prestamo $prestamo): int
    {
        // Hay interés del período anterior pendiente → devolver solo lo que falta.
        // proximo no avanzó; este es el monto que cierra ese período.
        if ($prestamo->interes_pendiente > 0) {
            return (int) $prestamo->interes_pendiente;
        }

        if ($prestamo->monto <= 0 || $prestamo->saldo <= 0) {
            return 0;
        }

        $mitad = $prestamo->monto / 2;
        if ($prestamo->monto >= 200000) {
            $base = $prestamo->saldo > $mitad ? $prestamo->monto : $mitad;
        } else {
            $base = $prestamo->saldo;
        }

        return (int) round($base * $this->tasa($prestamo));
    }

    // ── Multa por atraso ──────────────────────────────────────────────────────

    /** Tarifa diaria de multa según el monto original del préstamo (§5.5).
     *  < 100k → 2.000 | 100k–149.999 → 3.000 | ≥ 150k → 5.000 */
    public function multaPorDia(int $monto): int
    {
        foreach (self::TARIFAS_MULTA as $tope => $tarifa) {
            if ($monto <= $tope) {
                return $tarifa;
            }
        }

        return self::TARIFA_MULTA_DEFAULT;
    }

    /**
     * Multa neta que debe el cliente hoy (§5.5 + §5.9 Opción B).
     *
     * La multa_bruta se calcula de dos formas:
     *   - multa_acumulada > 0: monto congelado (ingresado al migrar, o resto tras
     *     un pago parcial con interés completo). No crece con el motor.
     *   - multa_acumulada = 0: dinámica: dias_atraso × tarifa. Crece cada día.
     *
     * multa_ya_pagada es el crédito acumulado de pagos de multa en el ciclo
     * de atraso actual. Se resta de la bruta para no cobrar dos veces lo ya pagado.
     *
     * Ejemplo: dia 6, bruta = ₡18.000, multa_ya_pagada = ₡10.000 → neta = ₡8.000.
     */
    public function multaAcumulada(Prestamo $prestamo): int
    {
        // Calcular la multa bruta (congelada o dinámica)
        if ($prestamo->multa_acumulada > 0) {
            // Congelada: ingresada manualmente al migrar el préstamo,
            // o resto de una multa tras pagar el interés completo.
            $bruta = (int) $prestamo->multa_acumulada;
        } elseif ($prestamo->dias_atraso > 0) {
            // Dinámica: se acumula un día a la vez con el motor de atraso.
            $bruta = $this->multaPorDia($prestamo->monto) * $prestamo->dias_atraso;
        } else {
            return 0;
        }

        // Restar crédito acumulado de pagos parciales de multa.
        // max(0, ...) evita valores negativos si hubo algún desajuste.
        return max(0, $bruta - (int) $prestamo->multa_ya_pagada);
    }

    // ── Intereses atrasados ───────────────────────────────────────────────────

    /**
     * Total de intereses atrasados que aún debe el cliente (§5.6 + §5.9).
     *
     * Descuenta los pagos parciales registrados en cada registro (monto_pagado).
     * Solo suma registros con pagado = false; los totalmente pagados ya no cuentan.
     */
    public function interesesAtrasadosTotal(Prestamo $prestamo): int
    {
        return (int) $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sum(fn($ia) => $ia->monto - $ia->monto_pagado);
    }

    // ── Totales auxiliares ────────────────────────────────────────────────────

    /**
     * Monto total para saldar la cuenta completa hoy (§5.7).
     * Incluye el interés del período si ya venció (cobrarInteres = true).
     * total = saldo + interés del período pendiente + intereses atrasados + multa
     */
    public function totalASaldar(Prestamo $prestamo): int
    {
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
            || $prestamo->interes_pendiente > 0;

        return $prestamo->saldo
            + ($cobrarInteres ? $this->interesPeriodo($prestamo) : 0)
            + $this->interesesAtrasadosTotal($prestamo)
            + $this->multaAcumulada($prestamo);
    }

    /** Cuánto ha abonado el cliente al capital (monto original − saldo actual). */
    public function abonado(Prestamo $prestamo): int
    {
        return max(0, $prestamo->monto - $prestamo->saldo);
    }

    // ── Cálculo de días de atraso ─────────────────────────────────────────────

    /**
     * Días de multa por atraso desde atraso_desde (§5.5).
     *
     * La multa arranca el día SIGUIENTE a la fecha de cobro vencida.
     * diffInDays(atraso_desde, hoy) da exactamente ese conteo:
     *   ej. cobro el 30 → hoy es el 5 del mes siguiente → 5 días.
     *
     * (Ver docblock en la versión anterior para la explicación del +1 implícito.)
     */
    public function calcularDiasAtraso(string|Carbon $atrasoDesde): int
    {
        $fecha = $atrasoDesde instanceof Carbon
            ? $atrasoDesde
            : Carbon::parse($atrasoDesde);

        return max(0, $fecha->copy()->startOfDay()->diffInDays(now()->startOfDay()));
    }

    // ── Operaciones de migración manual (§8 del README) ──────────────────────

    /**
     * Actualiza el préstamo migrado desde el formulario de edición del cliente.
     *
     * ADVERTENCIA: borra y recrea todos los InteresAtrasado no pagados.
     * Solo correcto para la fase de migración manual (cuaderno). Una vez que
     * el motor de Pagos empiece a generar registros período a período, no usar.
     */
    public function actualizarDesdeMigracion(Prestamo $prestamo, array $datos): void
    {
        $atrasoDesde        = $datos['atraso_desde'] ?? null;
        $diasAtraso         = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada     = $datos['multa_acumulada'] ?? 0;
        $interesesAtrasados = $datos['intereses_atrasados'] ?? 0;

        $atrasado = $diasAtraso > 0 || $multaAcumulada > 0 || $interesesAtrasados > 0;

        $prestamo->update([
            'monto'            => $datos['monto'],
            'saldo'            => $datos['saldo'] ?? $datos['monto'],
            'frecuencia'       => $datos['frecuencia'],
            'interes_pagados'  => $datos['interes_pagados'] ?? 0,
            'multa_acumulada'  => $multaAcumulada,
            'dias_atraso'      => $diasAtraso,
            'atraso_desde'     => $atrasoDesde,
            'inicio'           => $datos['inicio'],
            'proximo'          => $datos['proximo'],
            'vencido'          => $atrasado,
        ]);

        $prestamo->interesesAtrasados()->where('pagado', false)->delete();

        if ($interesesAtrasados > 0) {
            $prestamo->interesesAtrasados()->create([
                'fecha'  => $atrasoDesde ?? $datos['proximo'],
                'monto'  => $interesesAtrasados,
                'pagado' => false,
            ]);
        }

        $prestamo->cliente->update(['estado' => $atrasado ? 'atrasado' : 'al-dia']);
    }

    // ── Avance de fecha (reutilizado por motor de atraso y PagoService) ─────────

    /**
     * Avanza una fecha exactamente un período según la frecuencia del préstamo.
     * Método público para que PagoService pueda delegar aquí y evitar duplicación.
     */
    public function avanzarFecha(Carbon $fecha, string $frecuencia): Carbon
    {
        return match ($frecuencia) {
            'mensual'   => $fecha->copy()->addMonthNoOverflow(),
            'semanal'   => $fecha->copy()->addDays(7),
            'quincenal' => $this->proximoQuincenal($fecha),
            default     => $fecha->copy()->addMonthNoOverflow(),
        };
    }

    private function proximoQuincenal(Carbon $fecha): Carbon
    {
        if ($fecha->day <= 15) {
            return $fecha->copy()->setDay(min(30, $fecha->daysInMonth));
        }

        return $fecha->copy()->addMonthNoOverflow()->setDay(15);
    }

    // ── Motor de atraso ───────────────────────────────────────────────────────

    /**
     * Sincroniza el estado de atraso de un préstamo activo con el calendario real.
     *
     * Idempotente: correrlo N veces el mismo día produce el mismo resultado.
     * No paga nada ni avanza proximo — solo lleva la cuenta de lo vencido.
     * Es SIMÉTRICO: tanto marca como desmarca el atraso según el estado real.
     *
     * Requiere: $prestamo->interesesAtrasados cargado (eager load) y $prestamo->cliente.
     */
    public function sincronizarAtraso(Prestamo $prestamo): void
    {
        // Guard: nada que sincronizar si proximo no ha vencido y no hay deuda pendiente.
        // La comparación es ESTRICTA (lessThan): proximo = hoy significa "vence hoy",
        // no atrasado. La multa arranca el día SIGUIENTE al vencimiento.
        $proxVencido         = $prestamo->proximo->lessThan(today());
        $interesesPendientes = $prestamo->interesesAtrasados->where('pagado', false);
        $hayPendiente        = $prestamo->interes_pendiente > 0
            || $prestamo->multa_acumulada > 0
            || $prestamo->dias_atraso > 0
            || $interesesPendientes->isNotEmpty();

        // ── Camino de limpieza (simétrico al de marcar atraso) ───────────────
        // Si el préstamo ya NO está vencido y no tiene intereses atrasados
        // reales pendientes: limpiar todas las marcas de atraso y devolver el
        // cliente a 'al-dia'. Esto resuelve estados zombie (ej. datos erróneos
        // del bug de UTC, o multa_acumulada residual de ciclos anteriores).
        if (!$proxVencido && $interesesPendientes->isEmpty() && !$prestamo->interes_pendiente) {
            $limpieza = [];
            if ((int) $prestamo->dias_atraso     !== 0)   $limpieza['dias_atraso']    = 0;
            if ($prestamo->atraso_desde          !== null) $limpieza['atraso_desde']   = null;
            if ($prestamo->vencido)                        $limpieza['vencido']        = false;
            if ((int) $prestamo->multa_acumulada !== 0)    $limpieza['multa_acumulada'] = 0;
            if ((int) $prestamo->multa_ya_pagada !== 0)    $limpieza['multa_ya_pagada'] = 0;
            if ($limpieza) {
                $prestamo->update($limpieza);
            }
            if ($prestamo->cliente->estado !== 'al-dia') {
                $prestamo->cliente->update(['estado' => 'al-dia']);
            }
            return;
        }

        if (!$proxVencido && !$hayPendiente) {
            return;
        }

        $updates = [];

        // Paso 1: Setear atraso_desde si falta (primera detección del vencimiento).
        // atraso_desde = la fecha en que debía pagar y no pagó = proximo.
        // El motor NO reescribe atraso_desde si ya viene del pago o de migración.
        if ($proxVencido && $prestamo->atraso_desde === null) {
            $updates['atraso_desde'] = $prestamo->proximo->toDateString();
            $updates['vencido']      = true;
            // Reflejo en memoria para que calcularDiasAtraso en el paso 2 lo lea.
            $prestamo->atraso_desde = $prestamo->proximo->copy();
        }

        // Paso 2: Recalcular dias_atraso (fuente de verdad = atraso_desde).
        // Solo actualiza si el valor cambió (evita writes innecesarios cada visita).
        if ($prestamo->atraso_desde !== null) {
            $nuevosDias = $this->calcularDiasAtraso($prestamo->atraso_desde);
            if ($nuevosDias !== (int) $prestamo->dias_atraso) {
                $updates['dias_atraso'] = $nuevosDias;
            }
        }

        if ($updates) {
            $prestamo->update($updates);
        }

        // Paso 3: Cleanup anti-doble-cobro.
        // Si proximo avanzó (Camino A de PagoService) a una fecha que el motor había
        // registrado como período vencido, ese registro sobra: proximo ya lo cubre
        // como "interés del período actual". Solo se borra si no tiene ningún abono
        // (monto_pagado = 0). Si ya recibió dinero parcial, se respeta.
        $prestamo->interesesAtrasados()
            ->where('fecha', $prestamo->proximo->toDateString())
            ->where('pagado', false)
            ->where('monto_pagado', 0)
            ->delete();

        // Paso 4: Generar períodos vencidos POSTERIORES a proximo.
        // El cursor parte de avanzarFecha(proximo), nunca de proximo mismo,
        // porque proximo ya está cubierto por interesPeriodo().
        // La condición del while es ESTRICTA (lessThan): hoy no genera registro nuevo,
        // solo los días que ya pasaron completamente.
        $fechasExistentes = $prestamo->interesesAtrasados
            ->pluck('fecha')
            ->map(fn($f) => $f instanceof Carbon ? $f->toDateString() : (string) $f)
            ->flip()
            ->all();

        // Calcular el interés completo del período (sin interesPeriodo() porque ese
        // método devuelve interes_pendiente cuando hay pago parcial del período actual,
        // lo que sería incorrecto para períodos futuros completamente impagos).
        $mitad        = $prestamo->monto / 2;
        $base         = $prestamo->saldo > $mitad ? $prestamo->monto : $mitad;
        $montoInteres = (int) round($base * $this->tasa($prestamo));

        $proximo = $prestamo->proximo instanceof Carbon
            ? $prestamo->proximo->copy()
            : Carbon::parse($prestamo->proximo);

        $cursor = $this->avanzarFecha($proximo, $prestamo->frecuencia);

        while ($cursor->lessThan(today())) {
            $fechaStr = $cursor->toDateString();

            if (!array_key_exists($fechaStr, $fechasExistentes)) {
                $prestamo->interesesAtrasados()->create([
                    'fecha'  => $fechaStr,
                    'monto'  => $montoInteres,
                    'pagado' => false,
                ]);
                $fechasExistentes[$fechaStr] = true; // evita duplicado dentro del mismo run
            }

            $cursor = $this->avanzarFecha($cursor, $prestamo->frecuencia);
        }

        // Paso 5: Actualizar estado del cliente.
        // Reload para leer los registros recién creados/borrados en DB.
        $prestamo->refresh();
        $prestamo->load('interesesAtrasados');

        $hayDeuda = $prestamo->proximo->lessThan(today())
            || $prestamo->interes_pendiente > 0
            || $this->interesesAtrasadosTotal($prestamo) > 0
            || $this->multaAcumulada($prestamo) > 0;

        $estadoCliente = $hayDeuda ? 'atrasado' : 'al-dia';

        if ($prestamo->cliente->estado !== $estadoCliente) {
            $prestamo->cliente->update(['estado' => $estadoCliente]);
        }
    }

    // ── Nuevo préstamo (cliente existente) ───────────────────────────────────

    /**
     * Crea un nuevo préstamo para un cliente que no tiene uno activo.
     * El préstamo anterior (si existe) debe estar en estado 'saldado'; no lo toca.
     * Pone al cliente en 'al-dia'.
     */
    public function crear(Cliente $cliente, array $datos): Prestamo
    {
        $prestamo = $cliente->prestamos()->create([
            'monto'      => (int) $datos['monto'],
            'saldo'      => (int) $datos['monto'],
            'frecuencia' => $datos['frecuencia'],
            'inicio'     => $datos['inicio'],
            'proximo'    => $datos['proximo'],
            'estado'     => 'activo',
        ]);

        $cliente->update(['estado' => 'al-dia']);

        return $prestamo;
    }

    // ── Operaciones de migración manual (§8 del README) ──────────────────────

    /**
     * Crea el préstamo de un cliente migrado a mano desde el cuaderno (§8).
     * Ver ADVERTENCIA en actualizarDesdeMigracion().
     */
    public function crearDesdeMigracion(Cliente $cliente, array $datos): Prestamo
    {
        $atrasoDesde        = $datos['atraso_desde'] ?? null;
        $diasAtraso         = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada     = $datos['multa_acumulada'] ?? 0;
        $interesesAtrasados = $datos['intereses_atrasados'] ?? 0;

        $atrasado = $diasAtraso > 0 || $multaAcumulada > 0 || $interesesAtrasados > 0;

        $prestamo = $cliente->prestamos()->create([
            'monto'           => $datos['monto'],
            'saldo'           => $datos['saldo'] ?? $datos['monto'],
            'frecuencia'      => $datos['frecuencia'],
            'interes_pagados' => $datos['interes_pagados'] ?? 0,
            'multa_acumulada' => $multaAcumulada,
            'dias_atraso'     => $diasAtraso,
            'atraso_desde'    => $atrasoDesde,
            'inicio'          => $datos['inicio'],
            'proximo'         => $datos['proximo'],
            'vencido'         => $atrasado,
            'estado'          => 'activo',
        ]);

        if ($interesesAtrasados > 0) {
            // La fecha del interés atrasado es la del cobro que no se pagó (§5.6).
            $prestamo->interesesAtrasados()->create([
                'fecha'  => $atrasoDesde ?? $datos['proximo'],
                'monto'  => $interesesAtrasados,
                'pagado' => false,
            ]);
        }

        $cliente->update(['estado' => $atrasado ? 'atrasado' : 'al-dia']);

        return $prestamo;
    }
}
