<x-guest-layout>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo"><b>₡</b></div>
            <h1>Confirmá tu contraseña</h1>
            <div class="tag-line">Esta es un área protegida. Confirmá tu contraseña para continuar.</div>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="inp">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                    </div>
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <button type="submit" class="btn-enter">Confirmar</button>
            </form>
        </div>
    </div>
</x-guest-layout>
