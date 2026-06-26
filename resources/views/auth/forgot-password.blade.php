<x-guest-layout>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo"><b>₡</b></div>
            <h1>Recuperar contraseña</h1>
            <div class="tag-line">Te enviaremos un enlace para crear una nueva contraseña.</div>

            <x-auth-session-status class="mb-3" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="inp">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <button type="submit" class="btn-enter">Enviar enlace de recuperación</button>

                <div class="login-foot">
                    <a href="{{ route('login') }}">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
