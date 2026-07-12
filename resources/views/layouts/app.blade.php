<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#075E54">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Gestión">

    <title>{{ config('app.name', 'ControlPresta') }}</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/controlpresta.css') }}" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <aside class="desk-side d-none d-md-flex">
            @include('layouts.partials.sidebar-nav')
        </aside>

        <div class="offcanvas offcanvas-start desk-side-offcanvas" tabindex="-1" id="sidebarOffcanvas">
            <button type="button" class="btn-close btn-close-white offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            @include('layouts.partials.sidebar-nav')
        </div>

        <div class="desk-main flex-grow-1">
            <div class="mobile-topbar d-flex d-md-none">
                <button type="button" class="mobile-menu-btn" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <b>Gestión Interna</b>
            </div>
            <div class="desk-topbar">
                {{ $header ?? '' }}
            </div>
            <div class="desk-content">
                @if (session('status'))
                    <div class="flash-ok">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="flash-err">{{ session('error') }}</div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
