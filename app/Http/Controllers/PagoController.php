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

    public function create(Cliente $cliente)
    {
        $prestamo = $this->prestamo($cliente);
        $prestamo->load('interesesAtrasados');

        // Solo cobrar interés si ya llegó o pasó la fecha de cobro
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo);

        $interes      = $cobrarInteres ? $this->prestamoService->interesPeriodo($prestamo) : 0;
        $multa        = $cobrarInteres ? $this->prestamoService->multaAcumulada($prestamo) : 0;
        $interesesAtr = $cobrarInteres ? $this->prestamoService->interesesAtrasadosTotal($prestamo) : 0;

        return view('pagos.create', [
            'cliente'       => $cliente,
            'prestamo'      => $prestamo,
            'cobrarInteres' => $cobrarInteres,
            'interes'       => $interes,
            'multa'         => $multa,
            'interesesAtr'  => $interesesAtr,
            'minimo'        => $interes + $multa + $interesesAtr,
        ]);
    }

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
