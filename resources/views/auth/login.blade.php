<x-guest-layout>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo"><b>₡</b></div>
            <h1>ControlPresta</h1>
            <div class="tag-line">controlpresta.com</div>

            <x-auth-session-status class="mb-3" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="inp">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="inp">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                    </div>
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <button type="submit" class="btn-enter">Entrar</button>

                <div class="login-foot">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
