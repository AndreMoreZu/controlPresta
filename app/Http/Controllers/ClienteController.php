<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\PrestamoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function edit(Cliente $cliente)
    {
        abort_unless($cliente->activo, 404);

        $prestamo = $cliente->prestamos()
            ->with('interesesAtrasados')
            ->where('estado', 'activo')
            ->latest('inicio')
            ->first();

        return view('clientes.edit', [
            'cliente'  => $cliente,
            'prestamo' => $prestamo,
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $datos = $request->validated();

        $fotoFrente = $cliente->cedula_foto_frente;
        $fotoAtras  = $cliente->cedula_foto_atras;

        if ($request->hasFile('cedula_foto_frente')) {
            if ($fotoFrente) {
                Storage::disk('public')->delete($fotoFrente);
            }
            $fotoFrente = $request->file('cedula_foto_frente')->store('cedulas', 'public');
        }

        if ($request->hasFile('cedula_foto_atras')) {
            if ($fotoAtras) {
                Storage::disk('public')->delete($fotoAtras);
            }
            $fotoAtras = $request->file('cedula_foto_atras')->store('cedulas', 'public');
        }

        $cliente->update([
            'nombre'             => $datos['nombre'],
            'apellidos'          => $datos['apellidos'],
            'telefono'           => $datos['telefono'] ?? null,
            'direccion'          => $datos['direccion'] ?? null,
            'trabajo'            => $datos['trabajo'] ?? null,
            'cedula'             => $datos['cedula'] ?? null,
            'cedula_foto_frente' => $fotoFrente,
            'cedula_foto_atras'  => $fotoAtras,
        ]);

        if ($request->boolean('tiene_prestamo')) {
            $prestamo = $cliente->prestamos()
                ->where('estado', 'activo')
                ->latest('inicio')
                ->first();

            if ($prestamo) {
                $this->prestamoService->actualizarDesdeMigracion($prestamo, $datos);
            }
        }

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function desactivar(Cliente $cliente)
    {
        $cliente->update(['activo' => false]);

        return redirect()
            ->route('clientes.index')
            ->with('status', 'Cliente desactivado.');
    }

    public function inactivos()
    {
        $clientes = Cliente::query()
            ->where('activo', false)
            ->with('prestamos')
            ->orderBy('nombre')
            ->get();

        return view('clientes.inactivos', [
            'clientes' => $clientes,
        ]);
    }

    public function reactivar(Cliente $cliente)
    {
        $cliente->update(['activo' => true]);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Cliente reactivado.');
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
