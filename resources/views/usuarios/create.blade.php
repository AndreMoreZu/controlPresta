<x-app-layout>
    <x-slot name="header">Nuevo usuario</x-slot>

    <a href="{{ route('usuarios.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a Usuarios
    </a>

    <div class="panel form-container">
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf

            <h3>Datos del usuario</h3>

            <div class="form-field">
                <label for="name">Nombre</label>
                <div class="ctrl">
                    <input type="text" id="name" name="name"
                           value="{{ old('name') }}" autocomplete="off" required>
                </div>
                <x-input-error :messages="$errors->get('name')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="email">Correo electrónico</label>
                <div class="ctrl">
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}" autocomplete="off" required>
                </div>
                <x-input-error :messages="$errors->get('email')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="password">Contraseña</label>
                <div class="ctrl">
                    <input type="password" id="password" name="password"
                           autocomplete="new-password" required>
                </div>
                <x-input-error :messages="$errors->get('password')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirmar contraseña</label>
                <div class="ctrl">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           autocomplete="new-password" required>
                </div>
            </div>

            <div class="form-divider"></div>
            <button type="submit" class="btn-save">Crear usuario</button>
        </form>
    </div>

</x-app-layout>
