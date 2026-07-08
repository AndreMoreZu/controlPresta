<div class="side-head">
    <div class="logo">₡</div>
    <div>
        <b>Gestión Interna</b>
        <span>Préstamos</span>
    </div>
</div>
<nav class="desk-nav">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Panel</a>
    <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}">Clientes</a>
</nav>
<div class="side-foot">
    <div class="avd">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</div>
    <div class="flex-grow-1">
        <b>{{ auth()->user()->name }}</b>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</div>
