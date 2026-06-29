<x-app-layout>
    <x-slot name="header">
        Ficha de cliente
    </x-slot>

    <a href="{{ route('clientes.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a clientes
    </a>

    <div class="ficha-hero">
        <div class="big-av">{{ Str::upper(Str::substr($cliente->nombre, 0, 1).Str::substr($cliente->apellidos, 0, 1)) }}</div>
        <div>
            <h2>{{ $cliente->nombre }} {{ $cliente->apellidos }}</h2>
            <div class="meta">
                @if ($cliente->estado === 'atrasado')
                    <span class="tag late">Atrasado</span>
                @else
                    <span class="tag ok">Al día</span>
                @endif
                &nbsp; Cédula {{ $cliente->cedula ?? '—' }}
            </div>
        </div>
    </div>

    <div class="ficha-body">
        <div class="actions-main">
            <button type="button" class="btn-sin-icono" disabled>Registrar pago</button>
            <button type="button" class="btn-sin-icono" disabled>Nuevo préstamo</button>
        </div>
        <div class="note-locked">Estas acciones todavía no están disponibles.</div>

        @if ($prestamo)
            <div class="panel">
                <h3>Préstamo activo</h3>
                <div class="kv"><span class="k">Monto prestado</span><span class="v">{{ colones($prestamo->monto) }}</span></div>
                <div class="kv"><span class="k">Ha abonado</span><span class="v" style="color: var(--accent-dark)">{{ colones($service->abonado($prestamo)) }}</span></div>
                <div class="kv"><span class="k">Saldo actual</span><span class="v saldo">{{ colones($prestamo->saldo) }}</span></div>
                <div class="kv"><span class="k">Frecuencia</span><span class="v">{{ ucfirst($prestamo->frecuencia) }}</span></div>
                <div class="kv"><span class="k">Interés del período ({{ round($service->tasa($prestamo) * 100) }}%)</span><span class="v interes">{{ colones($service->interesPeriodo($prestamo)) }}</span></div>
                @if ($prestamo->interes_pagados > 0)
                    <div class="kv"><span class="k">Intereses ya pagados</span><span class="v">{{ colones($prestamo->interes_pagados) }}</span></div>
                @endif
                <div class="kv">
                    <span class="k">Próximo pago</span>
                    <span class="v {{ $prestamo->vencido ? 'due' : '' }}">
                        {{ $prestamo->proximo?->format('d/m/Y') ?? '—' }}{{ $prestamo->vencido ? ' · vencido' : '' }}
                    </span>
                </div>
                <div class="kv"><span class="k">Fecha primer cobro</span><span class="v">{{ $prestamo->inicio?->format('d/m/Y') ?? '—' }}</span></div>
            </div>
        @else
            <div class="panel">
                <h3>Préstamo activo</h3>
                <div style="color: var(--muted); font-size: 13.5px;">Este cliente no tiene un préstamo activo.</div>
            </div>
        @endif

        @if ($prestamo)
            @php
                $multa = $service->multaAcumulada($prestamo);
                $interesesAtr = $service->interesesAtrasadosTotal($prestamo);
                $totalSaldar = $service->totalASaldar($prestamo);
            @endphp

            @if ($multa > 0)
                <div class="panel panel-multa">
                    <h3>Multa por atraso<span class="sub">Monto registrado · aparte de la deuda y del interés</span></h3>
                    <div class="multa-banner">
                        <div class="l">{{ $prestamo->dias_atraso }} días de atraso<b>Multa acumulada</b></div>
                        <div class="d">{{ colones($multa) }}</div>
                    </div>
                    @if ($prestamo->atraso_desde)
                        <div class="kv"><span class="k">Vencido desde</span><span class="v">{{ $prestamo->atraso_desde->format('d/m/Y') }}</span></div>
                    @endif
                </div>
            @endif

            @if ($interesesAtr > 0)
                <div class="panel panel-interes-atrasado">
                    <h3>Intereses no pagados acumulados<span class="sub">Intereses que el cliente no ha pagado en cada período</span></h3>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #f3e8d8;">
                        <span style="font-weight:600;color:#b47d3a;">Total intereses atrasados</span>
                        <span style="font-size:20px;font-weight:800;color:#b47d3a;">{{ colones($interesesAtr) }}</span>
                    </div>
                    @foreach ($prestamo->interesesAtrasados->where('pagado', false) as $atraso)
                        <div class="interes-row">
                            <span class="il"><b>{{ $atraso->fecha->format('d/m/Y') }}</b> · interés no pagado</span>
                            <span class="im">{{ colones($atraso->monto) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($totalSaldar > 0)
                <div class="panel" style="border-color: var(--accent); background: #f4faf7;">
                    <h3 style="color: var(--accent-dark);">Resumen de deuda total</h3>
                    <div class="kv"><span class="k">Deuda principal</span><span class="v">{{ colones($prestamo->saldo) }}</span></div>
                    <div class="kv"><span class="k">Multa por atraso</span><span class="v" style="color: var(--red);">{{ colones($multa) }}</span></div>
                    <div class="kv"><span class="k">Intereses no pagados</span><span class="v" style="color: #b47d3a;">{{ colones($interesesAtr) }}</span></div>
                    <div class="kv" style="border-top: 2px solid var(--line); padding-top: 12px; margin-top: 4px;">
                        <span class="k" style="font-weight: 700;">Total adeudado</span>
                        <span class="v" style="font-size: 20px; font-weight: 800; color: var(--accent-dark);">{{ colones($totalSaldar) }}</span>
                    </div>
                </div>
            @endif
        @endif

        <div class="panel">
            <h3>Datos del cliente</h3>
            <div class="kv"><span class="k">Teléfono</span><span class="v">{{ $cliente->telefono ?? '—' }}</span></div>
            <div class="kv"><span class="k">Dirección</span><span class="v">{{ $cliente->direccion ?? '—' }}</span></div>
            <div class="kv"><span class="k">Trabajo</span><span class="v">{{ $cliente->trabajo ?? '—' }}</span></div>
        </div>

        <div class="panel">
            <h3>Documento de identidad</h3>
            <div class="cedula-grid">
                @foreach (['Frente' => $cliente->cedula_foto_frente, 'Atrás' => $cliente->cedula_foto_atras] as $lado => $foto)
                    <div class="cedula">
                        @if ($foto)
                            <img src="{{ asset('storage/'.$foto) }}" alt="{{ $lado }} de la cédula">
                            <div class="cap">{{ $lado }}</div>
                        @else
                            <svg viewBox="0 0 320 180" xmlns="http://www.w3.org/2000/svg">
                                <rect width="320" height="180" fill="#f3f5f4"/><rect width="320" height="34" fill="#075E54"/>
                                <text x="14" y="22" fill="#fff" font-family="Arial" font-size="11" font-weight="bold">REPÚBLICA DE COSTA RICA</text>
                                <rect x="14" y="48" width="74" height="92" rx="6" fill="#d7e0dc"/><circle cx="51" cy="78" r="16" fill="#aebfb8"/>
                                <path d="M31 132c0-12 9-20 20-20s20 8 20 20z" fill="#aebfb8"/>
                                <text x="102" y="62" fill="#8696a0" font-family="Arial" font-size="9">NOMBRE</text>
                                <text x="102" y="80" fill="#111B21" font-family="Arial" font-size="13" font-weight="bold">{{ $cliente->nombre }} {{ $cliente->apellidos }}</text>
                                <text x="102" y="112" fill="#8696a0" font-family="Arial" font-size="9">N.º DE CÉDULA</text>
                                <text x="102" y="130" fill="#111B21" font-family="Arial" font-size="14" font-weight="bold" letter-spacing="1">{{ $cliente->cedula }}</text>
                            </svg>
                            <div class="cap">{{ $lado }} (sin cargar)</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="actions-secondary">
            <button type="button" class="btn-ghost-sm" disabled>✎ Editar</button>
            <button type="button" class="btn-danger-sm" disabled>🗑 Desactivar</button>
        </div>

        <div class="panel" style="margin-top: 16px;">
            <h3>Historial de pagos<span class="sub">Cada pago muestra cuánto fue de interés y cuánto bajó la deuda</span></h3>
            @forelse (($prestamo?->pagos ?? collect())->sortByDesc('fecha') as $pago)
                <div class="hist-row">
                    <div class="h-l">
                        <b>{{ $pago->fecha->format('d/m/Y') }}</b>
                        <span><span class="int">Interés {{ colones($pago->interes) }}</span> · Abono {{ colones($pago->abono) }} · {{ ucfirst($pago->metodo) }}</span>
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
