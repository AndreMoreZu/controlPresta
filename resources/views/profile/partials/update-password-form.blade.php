<section>
    <h3>Cambiar contraseña</h3>
    <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">
        Usá una contraseña larga y aleatoria para mayor seguridad.
    </p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-field">
            <label for="update_password_current_password">Contraseña actual</label>
            <div class="ctrl">
                <input id="update_password_current_password" name="current_password"
                       type="password" autocomplete="current-password">
            </div>
            <x-input-error class="field-error" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="form-field">
            <label for="update_password_password">Nueva contraseña</label>
            <div class="ctrl">
                <input id="update_password_password" name="password"
                       type="password" autocomplete="new-password">
            </div>
            <x-input-error class="field-error" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="form-field">
            <label for="update_password_password_confirmation">Confirmar nueva contraseña</label>
            <div class="ctrl">
                <input id="update_password_password_confirmation" name="password_confirmation"
                       type="password" autocomplete="new-password">
            </div>
            <x-input-error class="field-error" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="form-divider"></div>
        <div style="display:flex; align-items:center; gap:12px;">
            <button type="submit" class="btn-save">Actualizar contraseña</button>
            @if (session('status') === 'password-updated')
                <span style="font-size:13px; color:var(--accent-dark);">Guardado.</span>
            @endif
        </div>
    </form>
</section>
