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
     * El interés del período siempre se muestra y precarga con el monto calculado.
     * El dueño edita lo que quiera; si cobra menos del interés calculado, el JS pide
     * confirmación antes de enviar (el período se cierra igual — no existe Camino B).
     */
    public function create(Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        $interes      = $this->prestamoService->interesPeriodo($prestamo);
        $multa        = $this->prestamoService->multaAcumulada($prestamo);
        $interesesAtr = $this->prestamoService->interesesAtrasadosTotal($prestamo);

        // Sugerir próxima fecha siempre desde HOY (nunca del proximo vencido).
        $proximoSugerido = $this->pagoService->sugerirProximo($prestamo)->format('Y-m-d');

        return view('pagos.create', [
            'cliente'         => $cliente,
            'prestamo'        => $prestamo,
            'interes'         => $interes,
            'multa'           => $multa,
            'interesesAtr'    => $interesesAtr,
            'proximoSugerido' => $proximoSugerido,
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

    /**
     * Formulario de saldar cuenta completa.
     *
     * Calcula cada concepto por separado para pasárselos al desglose editable.
     * cobrarInteres controla si el interés del período aparece en el desglose.
     */
    public function saldoCreate(Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        // cobrarInteres: controla la etiqueta "(pendiente)" en el blade, no oculta la fila.
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
            || $prestamo->interes_pendiente > 0;

        // Siempre calcular el interés del período para que el dueño pueda cobrarlo al saldar,
        // incluso si el cliente está al día (salda anticipado).
        $interes   = $this->prestamoService->interesPeriodo($prestamo);
        $multa     = $this->prestamoService->multaAcumulada($prestamo);
        $atrasados = $this->prestamoService->interesesAtrasadosTotal($prestamo);
        $total     = $prestamo->saldo + $interes + $atrasados + $multa;

        return view('pagos.saldar', [
            'cliente'       => $cliente,
            'prestamo'      => $prestamo,
            'cobrarInteres' => $cobrarInteres,
            'interes'       => $interes,
            'multa'         => $multa,
            'atrasados'     => $atrasados,
            'total'         => $total,
        ]);
    }

    /**
     * Ejecuta la liquidación completa de la cuenta.
     *
     * Los montos de cada concepto vienen del formulario (editables por el operador).
     * PagoService::saldarCuenta() los clampea al máximo real de cada deuda.
     */
    public function saldoStore(Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        $metodo = request()->input('metodo');
        abort_unless(in_array($metodo, ['efectivo', 'sinpe', 'transferencia'], true), 422);

        $this->pagoService->saldarCuenta($prestamo, [
            'metodo'             => $metodo,
            'recibido_por'       => auth()->id(),
            'pago_interes'       => (int) request()->input('pago_interes', 0),
            'pago_multa'         => (int) request()->input('pago_multa', 0),
            'pago_intereses_atr' => (int) request()->input('pago_intereses_atr', 0),
        ]);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Cuenta saldada correctamente.');
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
