<x-app-layout>
    <x-slot name="header">
        Préstamo saldado
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    @php
        $ultimoPago = $prestamo->pagos->sortByDesc('fecha')->first();
    @endphp

    <div class="ficha-hero" style="border-radius: 14px 14px 0 0;">
        <div>
            <h2 style="font-size: 18px;">{{ colones($prestamo->monto) }}</h2>
            <div class="meta">
                <span class="tag ok" style="font-size: 10.5px; padding: 3px 10px;">Saldado</span>
                &nbsp; {{ ucfirst($prestamo->frecuencia) }}
            </div>
        </div>
    </div>

    <div class="ficha-body">
        <div class="panel">
            <h3>Resumen del préstamo</h3>
            <div class="kv">
                <span class="k">Monto original</span>
                <span class="v">{{ colones($prestamo->monto) }}</span>
            </div>
            <div class="kv">
                <span class="k">Saldo final</span>
                <span class="v" style="color: var(--accent-dark); font-weight: 800;">{{ colones($prestamo->saldo) }}</span>
            </div>
            <div class="kv">
                <span class="k">Frecuencia de pago</span>
                <span class="v">{{ ucfirst($prestamo->frecuencia) }}</span>
            </div>
            <div class="kv">
                <span class="k">Fecha de inicio</span>
                <span class="v">{{ $prestamo->inicio?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="kv">
                <span class="k">Fecha en que se saldó</span>
                <span class="v">{{ $ultimoPago?->fecha->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="kv">
                <span class="k">Total de intereses pagados</span>
                <span class="v interes">{{ colones($prestamo->interes_pagados) }}</span>
            </div>
            <div class="kv">
                <span class="k">Total de pagos registrados</span>
                <span class="v">{{ $prestamo->pagos->count() }}</span>
            </div>
        </div>

        <div class="panel">
            <h3>Historial completo de pagos<span class="sub">Todos los pagos de este préstamo, del más reciente al más antiguo</span></h3>
            @forelse ($prestamo->pagos->sortByDesc('fecha') as $pago)
                <div class="hist-row">
                    <div class="h-l">
                        <b>{{ $pago->fecha->format('d/m/Y') }}</b>
                        <span>
                            <span class="int">Interés {{ colones($pago->interes) }}</span>
                            · Abono {{ colones($pago->abono) }}
                            · {{ ucfirst($pago->metodo) }}
                        </span>
                    </div>
                    <div class="h-r">
                        <div class="h-amt">{{ colones($pago->monto_total) }}</div>
                        <div class="h-split">pago total</div>
                    </div>
                </div>
            @empty
                <div style="color: var(--muted); font-size: 13px;">Sin pagos registrados.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
