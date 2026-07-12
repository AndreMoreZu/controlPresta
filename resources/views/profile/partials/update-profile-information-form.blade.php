<section>
    <h3>Información del perfil</h3>
    <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">
        Actualizá tu nombre y correo electrónico.
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="form-field">
            <label for="name">Nombre</label>
            <div class="ctrl">
                <input id="name" name="name" type="text"
                       value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            </div>
            <x-input-error class="field-error" :messages="$errors->get('name')" />
        </div>

        <div class="form-field">
            <label for="email">Correo electrónico</label>
            <div class="ctrl">
                <input id="email" name="email" type="email"
                       value="{{ old('email', $user->email) }}" required autocomplete="username">
            </div>
            <x-input-error class="field-error" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p style="font-size:13px; color:var(--muted); margin-top:6px;">
                    Tu correo no está verificado.
                    <button form="send-verification" class="btn-link">Reenviar verificación.</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p style="font-size:13px; color:var(--accent-dark); margin-top:4px;">
                        Se envió un nuevo enlace de verificación a tu correo.
                    </p>
                @endif
            @endif
        </div>

        <div class="form-divider"></div>
        <div style="display:flex; align-items:center; gap:12px;">
            <button type="submit" class="btn-save">Guardar cambios</button>
            @if (session('status') === 'profile-updated')
                <span style="font-size:13px; color:var(--accent-dark);">Guardado.</span>
            @endif
        </div>
    </form>
</section>
