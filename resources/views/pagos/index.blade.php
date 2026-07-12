<x-app-layout>
    <x-slot name="header">
        Historial de pagos
    </x-slot>

    {{-- ── Filtros ──────────────────────────────────────────────────────────── --}}
    <div class="list-head">
        <form method="GET" action="{{ route('pagos.index') }}">
            <input type="hidden" name="metodo" value="{{ $metodo }}">
            <div class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar cliente…" autocomplete="off">
            </div>
            <div class="hist-date-row">
                <input type="date" name="desde" class="hist-date-input" value="{{ $desde }}" title="Desde">
                <span class="hist-date-sep">—</span>
                <input type="date" name="hasta" class="hist-date-input" value="{{ $hasta }}" title="Hasta">
                <button type="submit" class="hist-btn-filtrar">Filtrar</button>
                @if ($busqueda || $metodo || $desde || $hasta)
                    <a href="{{ route('pagos.index') }}" class="hist-btn-limpiar">✕</a>
                @endif
            </div>
        </form>
        <div class="chips">
            <a href="{{ route('pagos.index', array_filter(['q' => $busqueda, 'desde' => $desde, 'hasta' => $hasta])) }}"
               class="chip {{ !$metodo ? 'on' : '' }}">Todos</a>
            @foreach (['efectivo' => 'Efectivo', 'sinpe' => 'Sinpe', 'transferencia' => 'Transf.'] as $val => $lbl)
                <a href="{{ route('pagos.index', array_filter(['q' => $busqueda, 'desde' => $desde, 'hasta' => $hasta, 'metodo' => $val])) }}"
                   class="chip {{ $metodo === $val ? 'on' : '' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    {{-- ── Barra de totales ─────────────────────────────────────────────────── --}}
    @if ($totales->cantidad > 0)
        <div class="hist-totales-bar">
            <div class="ht-stat">
                <span class="ht-val">{{ number_format($totales->cantidad) }}</span>
                <span class="ht-lbl">pagos</span>
            </div>
            <div class="ht-sep"></div>
            <div class="ht-stat">
                <span class="ht-val">{{ colones($totales->monto_total) }}</span>
                <span class="ht-lbl">total cobrado</span>
            </div>
            <div class="ht-sep"></div>
            <div class="ht-stat">
                <span class="ht-val" style="color: var(--accent-dark);">{{ colones($totales->interes) }}</span>
                <span class="ht-lbl">intereses</span>
            </div>
            <div class="ht-sep"></div>
            <div class="ht-stat">
                <span class="ht-val">{{ colones($totales->abono) }}</span>
                <span class="ht-lbl">abonos</span>
            </div>
            @if ($totales->multa_pagada > 0)
                <div class="ht-sep"></div>
                <div class="ht-stat">
                    <span class="ht-val" style="color: var(--red);">{{ colones($totales->multa_pagada) }}</span>
                    <span class="ht-lbl">multas</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Lista de pagos ───────────────────────────────────────────────────── --}}
    <div class="panel gh-panel">
        @forelse ($pagos as $pago)
            @php $cliente = $pago->prestamo?->cliente; @endphp
            <div class="gh-item">
                <div class="gh-head">
                    <span class="gh-fecha">{{ $pago->fecha->format('d/m/Y') }}</span>
                    @if ($cliente)
                        <a href="{{ route('clientes.show', $cliente) }}" class="gh-cliente">
                            {{ $cliente->nombre }} {{ $cliente->apellidos }}
                        </a>
                    @else
                        <span class="gh-cliente gh-cliente--vacio">—</span>
                    @endif
                    @if ($pago->es_saldo)
                        <span class="tag tag-saldo">Saldado</span>
                    @endif
                    <span class="gh-total">{{ colones($pago->monto_total) }}</span>
                </div>
                <div class="gh-split">
                    @if ($pago->interes > 0)
                        <span class="gh-int">Interés {{ colones($pago->interes) }}</span>
                    @endif
                    @if ($pago->abono > 0)
                        <span>Abono {{ colones($pago->abono) }}</span>
                    @endif
                    @if ($pago->multa_pagada > 0)
                        <span class="gh-multa">Multa {{ colones($pago->multa_pagada) }}</span>
                    @endif
                    @if ($pago->interes_atrasado_pagado > 0)
                        <span class="gh-atr">Atr. {{ colones($pago->interes_atrasado_pagado) }}</span>
                    @endif
                    <span class="gh-metodo">{{ ucfirst($pago->metodo) }}</span>
                </div>
            </div>
        @empty
            <div class="empty-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="16" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
                <div>Sin pagos registrados</div>
            </div>
        @endforelse
    </div>

    {{-- ── Paginación ───────────────────────────────────────────────────────── --}}
    @if ($pagos->hasPages())
        <div class="gh-paginacion">
            {{ $pagos->links('pagination::bootstrap-5') }}
        </div>
    @endif

</x-app-layout>
