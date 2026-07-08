<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Models\Cliente;
use App\Models\Prestamo;
use App\Services\PagoService;
use App\Services\PrestamoService;

class PagoController extends Controller
{
    public function __construct(
        private PagoService $pagoService,
        private PrestamoService $prestamoService,
    ) {}

    /**
     * Formulario de registro de pago.
     *
     * Pasa al formulario los montos exactos que debe el cliente hoy por cada concepto,
     * para que los campos vengan precargados con el total (caso normal = confirmar y ya).
     * cobrarInteres controla si se muestra la tabla de conceptos o el banner "adelantado".
     */
    public function create(Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        // cobrarInteres = true si ya venció la fecha de cobro O si quedó interés
        // pendiente de un pago parcial anterior (en ese caso proximo tampoco avanzó).
        // cobrarInteres controla SOLO si el interés del período actual ya venció.
        // Multa e intereses atrasados son deudas independientes: pueden existir
        // aunque proximo esté en el futuro (caso borde §5.9).
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
            || $prestamo->interes_pendiente > 0;

        $interes      = $cobrarInteres ? $this->prestamoService->interesPeriodo($prestamo) : 0;
        // Siempre calcular multa e intereses atrasados, independientemente de proximo.
        $multa        = $this->prestamoService->multaAcumulada($prestamo);
        $interesesAtr = $this->prestamoService->interesesAtrasadosTotal($prestamo);

        // Fecha sugerida para el próximo cobro: solo aplica en Camino A (interés completo).
        // Si el cliente paga parcial (Camino B), proximo no avanza y este valor se ignora.
        $proximoSugerido = $cobrarInteres
            ? $this->pagoService->avanzarProximo($prestamo)->format('Y-m-d')
            : null;

        return view('pagos.create', [
            'cliente'          => $cliente,
            'prestamo'         => $prestamo,
            'cobrarInteres'    => $cobrarInteres,
            'interes'          => $interes,
            'multa'            => $multa,
            'interesesAtr'     => $interesesAtr,
            'proximoSugerido'  => $proximoSugerido,
        ]);
    }

    /**
     * Procesa el pago y redirige a la ficha del cliente.
     */
    public function store(StorePagoRequest $request, Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        $this->pagoService->registrarPago($prestamo, [
            ...$request->validated(),
            'recibido_por' => auth()->id(),
        ]);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Pago registrado correctamente.');
    }

    /** Obtiene el préstamo activo del cliente o aborta con 404. */
    private function prestamo(Cliente $cliente): Prestamo
    {
        $prestamo = $cliente->prestamos()
            ->where('estado', 'activo')
            ->latest('inicio')
            ->first();

        abort_unless($prestamo, 404);

        return $prestamo;
    }
}
