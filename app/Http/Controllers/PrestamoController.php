<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;

class PrestamoController extends Controller
{
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
}
