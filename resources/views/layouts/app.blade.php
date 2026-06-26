<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ControlPresta') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/controlpresta.css') }}" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <aside class="desk-side">
            <div class="side-head">
                <div class="logo">₡</div>
                <div>
                    <b>ControlPresta</b>
                    <span>Panel interno</span>
                </div>
            </div>
            <nav class="desk-nav">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Panel</a>
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
        </aside>

        <div class="desk-main flex-grow-1">
            <div class="desk-topbar">
                {{ $header ?? '' }}
            </div>
            <div class="desk-content">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
