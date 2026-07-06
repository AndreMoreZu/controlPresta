<x-app-layout>
    <x-slot name="header">
        Clientes inactivos
    </x-slot>

    <a href="{{ route('clientes.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a clientes activos
    </a>

    <div class="list-scroll" style="border-radius: 14px; border: 1px solid var(--line-soft);">
        @forelse ($clientes as $cliente)
            @php $prestamo = $cliente->prestamos->firstWhere('estado', 'activo'); @endphp
            <a href="{{ route('clientes.show', $cliente) }}" class="client">
                <div class="av" style="opacity: .45;">
                    {{ Str::upper(Str::substr($cliente->nombre, 0, 1).Str::substr($cliente->apellidos, 0, 1)) }}
                </div>
                <div class="info">
                    <b>{{ $cliente->nombre }} {{ $cliente->apellidos }}</b>
                    <div class="debt">
                        @if ($prestamo && $prestamo->saldo > 0)
                            Debe <strong>{{ colones($prestamo->saldo) }}</strong>
                        @else
                            Sin deuda activa
                        @endif
                    </div>
                </div>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @empty
            <div class="empty-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.5a3 3 0 0 1 0 5.8M18 20c0-2.4-1.1-4.5-2.8-5.6"/></svg>
                <div>No hay clientes inactivos.</div>
            </div>
        @endforelse
    </div>
</x-app-layout>
