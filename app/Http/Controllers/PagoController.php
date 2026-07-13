<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Services\PagoService;
use App\Services\PrestamoService;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function __construct(
        private PagoService $pagoService,
        private PrestamoService $prestamoService,
    ) {}

    public function index(Request $request)
    {
        $busqueda = trim($request->query('q', ''));
        $metodo   = $request->query('metodo', '');
        $desde    = $request->query('desde', '');
        $hasta    = $request->query('hasta', '');

        $totales = $this->queryPagos($busqueda, $metodo, $desde, $hasta)
            ->selectRaw('
                COUNT(*)                                  AS cantidad,
                COALESCE(SUM(monto_total), 0)             AS monto_total,
                COALESCE(SUM(interes), 0)                 AS interes,
                COALESCE(SUM(abono), 0)                   AS abono,
                COALESCE(SUM(interes_atrasado_pagado), 0) AS interes_atrasado_pagado,
                COALESCE(SUM(multa_pagada), 0)            AS multa_pagada
            ')
            ->first();

        $pagos = $this->queryPagos($busqueda, $metodo, $desde, $hasta)
            ->with('prestamo.cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('pagos.index', compact('pagos', 'totales', 'busqueda', 'metodo', 'desde', 'hasta'));
    }

    private function queryPagos(string $busqueda, string $metodo, string $desde, string $hasta)
    {
        return Pago::query()
            ->when($busqueda, fn($q) =>
                $q->whereHas('prestamo.cliente', fn($c) =>
                    $c->where('nombre',    'like', "%{$busqueda}%")
                      ->orWhere('apellidos', 'like', "%{$busqueda}%")
                )
            )
            ->when($metodo, fn($q) => $q->where('metodo', $metodo))
            ->when($desde,  fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta,  fn($q) => $q->whereDate('fecha', '<=', $hasta));
    }

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

        // Mostrar la fecha ya guardada en el préstamo (el dueño la edita si el cliente acordó otra).
        $proximoSugerido = $prestamo->proximo->format('Y-m-d');

        // El interés solo se precarga cuando ya le toca pagar (llegó o pasó la fecha de cobro).
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
            || $prestamo->interes_pendiente > 0;

        return view('pagos.create', [
            'cliente'         => $cliente,
            'prestamo'        => $prestamo,
            'interes'         => $interes,
            'cobrarInteres'   => $cobrarInteres,
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
