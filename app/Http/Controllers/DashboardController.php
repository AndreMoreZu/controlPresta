<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Services\PrestamoService;

class DashboardController extends Controller
{
    public function __construct(private PrestamoService $prestamoService) {}

    public function index()
    {
        // ── Sección 1: Atrasados ──────────────────────────────────────────────────
        // Carga interesesAtrasados porque interesesAtrasadosTotal() los necesita.
        // Orden: atraso_desde ASC → el que lleva más tiempo sin pagar va primero.
        $atrasados = Cliente::where('estado', 'atrasado')
            ->with(['prestamos' => fn($q) => $q
                ->where('estado', 'activo')
                ->with('interesesAtrasados')
                ->latest('inicio')
            ])
            ->get()
            ->filter(fn($c) => $c->prestamos->isNotEmpty())
            ->sortBy(fn($c) => optional($c->prestamos->first()->atraso_desde)->timestamp ?? PHP_INT_MAX)
            ->values();

        // ── Sección 2: Por cobrar esta semana ─────────────────────────────────────
        // Excluye atrasados para no duplicarlos con la sección 1.
        // interesPeriodo() no necesita interesesAtrasados cargados.
        $porCobrar = Prestamo::where('estado', 'activo')
            ->whereBetween('proximo', [today(), today()->addDays(7)])
            ->whereHas('cliente', fn($q) => $q->where('estado', '!=', 'atrasado'))
            ->with('cliente')
            ->orderBy('proximo')
            ->get();

        // ── Sección 3: Conteos y suma semanal (lunes a domingo) ───────────────────
        $conteoAlDia    = Cliente::where('estado', 'al-dia')->count();
        $conteoAtrasado = Cliente::where('estado', 'atrasado')->count();
        $pagosSemana    = (int) Pago::whereBetween('fecha', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ])->sum('monto_total');

        return view('dashboard', [
            'atrasados'      => $atrasados,
            'porCobrar'      => $porCobrar,
            'conteoAlDia'    => $conteoAlDia,
            'conteoAtrasado' => $conteoAtrasado,
            'pagosSemana'    => $pagosSemana,
            'service'        => $this->prestamoService,
        ]);
    }
}
