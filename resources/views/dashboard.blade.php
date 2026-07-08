<x-app-layout>
    <x-slot name="header">
        Panel principal
    </x-slot>

    <div class="dash-container">

    {{-- ══════════════════════════════════════════════════════════
         SECCIÓN 1: ATRASADOS — va primero, es lo más urgente.
         Ordenados por atraso_desde ASC: el que lleva más tiempo
         sin pagar aparece primero. Excluye clientes sin préstamo
         activo (filtrado en el controller).
         ══════════════════════════════════════════════════════════ --}}
    <div class="section-title dash-title--late">
        Atrasados
        @if ($atrasados->isNotEmpty())
            <span class="dash-badge">{{ $atrasados->count() }}</span>
        @endif
    </div>

    <div class="panel {{ $atrasados->isNotEmpty() ? 'dash-panel--late' : '' }}">
        @forelse ($atrasados as $cliente)
            @php
                $prestamo = $cliente->prestamos->first();

                // Calcular lo que debe hoy por cada concepto
                $multa   = $service->multaAcumulada($prestamo);
                $atr     = $service->interesesAtrasadosTotal($prestamo);

                // Interés del período: solo si ya venció proximo o hay pendiente (§5.9)
                $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
                    || $prestamo->interes_pendiente > 0;
                $interes = $cobrarInteres ? $service->interesPeriodo($prestamo) : 0;

                $totalLate  = $multa + $atr + $interes;

                // Días de atraso: calcular desde atraso_desde si existe (fuente de verdad)
                $diasAtraso = $prestamo->atraso_desde
                    ? $service->calcularDiasAtraso($prestamo->atraso_desde)
                    : $prestamo->dias_atraso;
            @endphp
            <a href="{{ route('clientes.show', $cliente) }}" class="dash-row">
                <div class="av dash-av-late">
                    {{ Str::upper(Str::substr($cliente->nombre, 0, 1) . Str::substr($cliente->apellidos, 0, 1)) }}
                </div>
                <div class="info">
                    <b>{{ $cliente->nombre }} {{ $cliente->apellidos }}</b>
                    <div class="debt">
                        Hace {{ $diasAtraso }} {{ $diasAtraso === 1 ? 'día' : 'días' }}
                        @if ($prestamo->atraso_desde)
                            &middot; desde {{ $prestamo->atraso_desde->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
                <div class="dash-total dash-total--late">
                    <span>{{ colones($totalLate) }}</span>
                    <small>total</small>
                </div>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 6 6 6-6 6"/>
                </svg>
            </a>
        @empty
            <p class="dash-empty">Sin atrasos por el momento</p>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECCIÓN 2: POR COBRAR ESTA SEMANA
         Préstamos activos con proximo en los próximos 7 días,
         excluyendo clientes atrasados (ya están en sección 1).
         Ordenados por proximo ASC: el más próximo primero.
         ══════════════════════════════════════════════════════════ --}}
    <div class="section-title">Por cobrar esta semana</div>

    <div class="panel">
        @forelse ($porCobrar as $prestamo)
            @php
                $interesCobrar = $service->interesPeriodo($prestamo);
                $esHoy         = $prestamo->proximo->isToday();
            @endphp
            <a href="{{ route('clientes.show', $prestamo->cliente) }}" class="dash-row">
                <div class="av dash-av-cobrar">
                    {{ Str::upper(Str::substr($prestamo->cliente->nombre, 0, 1) . Str::substr($prestamo->cliente->apellidos, 0, 1)) }}
                </div>
                <div class="info">
                    <b>{{ $prestamo->cliente->nombre }} {{ $prestamo->cliente->apellidos }}</b>
                    <div class="debt">
                        {{ $esHoy ? 'Cobrar hoy' : 'Cobra el ' . $prestamo->proximo->format('d/m') }}
                        &middot; {{ ucfirst($prestamo->frecuencia) }}
                    </div>
                </div>
                <div class="dash-total dash-total--cobrar">
                    <span>{{ colones($interesCobrar) }}</span>
                    <small>interés</small>
                </div>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 6 6 6-6 6"/>
                </svg>
            </a>
        @empty
            <p class="dash-empty">Nadie por cobrar esta semana</p>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECCIÓN 3: TARJETITAS DE CONTEXTO
         Datos de referencia rápida. Van al final porque las listas
         de arriba son lo accionable; estas son solo contexto.
         ══════════════════════════════════════════════════════════ --}}
    <div class="section-title" style="margin-top: 28px;">Resumen</div>
    <div class="cards five">
        <div class="stat stat--pago">
            <div class="lab">Total en la calle</div>
            <div class="val" style="color: var(--accent-dark);">{{ colones($totalEnLaCalle) }}</div>
            <div class="sub">Saldo préstamos activos</div>
        </div>
        <div class="stat stat--pago">
            <div class="lab">Total por cobrar</div>
            <div class="val" style="color: var(--accent-dark);">{{ colones($totalPorCobrar) }}</div>
            <div class="sub">Capital + multas + atrasos</div>
        </div>
        <div class="stat">
            <div class="lab">Clientes al día</div>
            <div class="val">{{ $conteoAlDia }}</div>
        </div>
        <div class="stat {{ $conteoAtrasado > 0 ? 'warn' : '' }}">
            <div class="lab">Clientes atrasados</div>
            <div class="val">{{ $conteoAtrasado }}</div>
        </div>
        <div class="stat stat--pago">
            <div class="lab">Pagos esta semana</div>
            <div class="val">{{ colones($pagosSemana) }}</div>
            <div class="sub">Lun a Dom</div>
        </div>
    </div>

    </div>{{-- /.dash-container --}}
</x-app-layout>
