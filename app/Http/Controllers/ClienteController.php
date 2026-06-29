<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
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

    public function create()
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
        $datos = $request->validated();

        $cliente = Cliente::create([
            'nombre' => $datos['nombre'],
            'apellidos' => $datos['apellidos'],
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'trabajo' => $datos['trabajo'] ?? null,
            'cedula' => $datos['cedula'] ?? null,
            'cedula_foto_frente' => $request->file('cedula_foto_frente')?->store('cedulas', 'public'),
            'cedula_foto_atras' => $request->file('cedula_foto_atras')?->store('cedulas', 'public'),
        ]);

        if ($request->boolean('tiene_prestamo')) {
            $this->prestamoService->crearDesdeMigracion($cliente, $datos);
        }

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Cliente creado correctamente.');
    }
}
