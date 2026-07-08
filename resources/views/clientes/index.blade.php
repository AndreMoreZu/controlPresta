<x-app-layout>
    <x-slot name="header">
        Clientes
    </x-slot>

    <div class="list-head">
        <form method="GET" action="{{ route('clientes.index') }}">
            <input type="hidden" name="filter" value="{{ $filtroActivo }}">
            <div class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar cliente…">
            </div>
        </form>
        <div class="chips">
            <a href="{{ route('clientes.index', ['q' => $busqueda]) }}" class="chip {{ $filtroActivo === 'todos' ? 'on' : '' }}">Todos</a>
            <a href="{{ route('clientes.index', ['q' => $busqueda, 'filter' => 'al-dia']) }}" class="chip {{ $filtroActivo === 'al-dia' ? 'on' : '' }}">Al día</a>
            <a href="{{ route('clientes.index', ['q' => $busqueda, 'filter' => 'atrasado']) }}" class="chip {{ $filtroActivo === 'atrasado' ? 'on' : '' }}">Atrasados</a>
            <a href="{{ route('clientes.inactivos') }}" class="chip chip-inactivos">Inactivos</a>
        </div>
        <a href="{{ route('clientes.create') }}" class="btn-new">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo cliente
        </a>
    </div>

    <div class="list-scroll">
        @forelse ($clientes as $cliente)
            @php $prestamo = $cliente->prestamos->firstWhere('estado', 'activo'); @endphp
            <a href="{{ route('clientes.show', $cliente) }}" class="client">
                <div class="av">{{ Str::upper(Str::substr($cliente->nombre, 0, 1).Str::substr($cliente->apellidos, 0, 1)) }}</div>
                <div class="info">
                    <b>{{ $cliente->nombre }} {{ $cliente->apellidos }}</b>
                    <div class="debt">Debe <strong>{{ colones($prestamo->saldo ?? 0) }}</strong></div>
                </div>
                @if ($cliente->estado === 'atrasado')
                    <span class="tag late">Atrasado</span>
                @elseif ($cliente->estado === 'sin-prestamo')
                    <span class="tag none">Sin préstamo</span>
                @else
                    <span class="tag ok">Al día</span>
                @endif
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @empty
            <div class="empty-detail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.5a3 3 0 0 1 0 5.8M18 20c0-2.4-1.1-4.5-2.8-5.6"/></svg>
                <div>Aún no hay clientes</div>
            </div>
        @endforelse
    </div>
</x-app-layout>
