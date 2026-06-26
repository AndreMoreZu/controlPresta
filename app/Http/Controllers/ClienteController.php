<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\PrestamoService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private PrestamoService $prestamoService)
    {
    }

    public function index(Request $request)
    {
        $clientes = Cliente::query()
            ->where('activo', true)
            ->with('prestamos')
            ->when($request->query('q'), function ($query, $q) {
                $query->where(function ($query) use ($q) {
                    $query->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellidos', 'like', "%{$q}%");
                });
            })
            ->when($request->query('filter'), function ($query, $filter) {
                if (in_array($filter, ['al-dia', 'atrasado'], true)) {
                    $query->where('estado', $filter);
                }
            })
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', [
            'clientes' => $clientes,
            'filtroActivo' => $request->query('filter', 'todos'),
            'busqueda' => $request->query('q', ''),
        ]);
    }

    public function show(Cliente $cliente)
    {
        abort_unless($cliente->activo, 404);

        $cliente->load(['prestamos.pagos', 'prestamos.interesesAtrasados']);

        $prestamo = $cliente->prestamos
            ->where('estado', 'activo')
            ->sortByDesc('inicio')
            ->first();

        return view('clientes.show', [
            'cliente' => $cliente,
            'prestamo' => $prestamo,
            'service' => $this->prestamoService,
        ]);
    }
}
