<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrestamoRequest;
use App\Models\Cliente;
use App\Models\Prestamo;
use App\Services\PrestamoService;

class PrestamoController extends Controller
{
    public function __construct(private PrestamoService $prestamoService) {}

    public function show(Cliente $cliente, Prestamo $prestamo)
    {
        abort_unless($prestamo->cliente_id === $cliente->id, 404);
        abort_unless($prestamo->estado === 'saldado', 404);

        $prestamo->load('pagos');

        return view('prestamos.show', [
            'cliente'  => $cliente,
            'prestamo' => $prestamo,
        ]);
    }

    public function create(Cliente $cliente)
    {
        // Solo si el cliente no tiene préstamo activo con saldo
        $tieneActivo = $cliente->prestamos()
            ->where('estado', 'activo')
            ->where('saldo', '>', 0)
            ->exists();

        abort_if($tieneActivo, 403);

        return view('prestamos.create', ['cliente' => $cliente]);
    }

    public function store(StorePrestamoRequest $request, Cliente $cliente)
    {
        $tieneActivo = $cliente->prestamos()
            ->where('estado', 'activo')
            ->where('saldo', '>', 0)
            ->exists();

        abort_if($tieneActivo, 403);

        $this->prestamoService->crear($cliente, $request->validated());

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Préstamo creado correctamente.');
    }
}
