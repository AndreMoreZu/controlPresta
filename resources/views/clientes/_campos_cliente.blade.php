{{--
    Partial: campos personales del cliente.
    Variable requerida: $cliente (null al crear, objeto Cliente al editar).
    El JS de formateo y fotos va en cada vista que incluya este partial.
--}}
@php
    $fotoFrente = $cliente?->cedula_foto_frente;
    $fotoAtras  = $cliente?->cedula_foto_atras;
@endphp

<div class="form-field">
    <label for="nombre">Nombre</label>
    <div class="ctrl">
        <input id="nombre" name="nombre" value="{{ old('nombre', $cliente?->nombre) }}" required autofocus>
    </div>
    <x-input-error :messages="$errors->get('nombre')" />
</div>

<div class="form-field">
    <label for="apellidos">Apellidos</label>
    <div class="ctrl">
        <input id="apellidos" name="apellidos" value="{{ old('apellidos', $cliente?->apellidos) }}" required>
    </div>
    <x-input-error :messages="$errors->get('apellidos')" />
</div>

<div class="form-field">
    <label for="cedula">Cédula</label>
    <p class="form-note">Sin guiones ni espacios.</p>
    <div class="ctrl">
        <input id="cedula" name="cedula" value="{{ old('cedula', $cliente?->cedula) }}">
    </div>
    <x-input-error :messages="$errors->get('cedula')" />
</div>

<div class="form-field">
    <label for="telefono">Teléfono</label>
    <p class="form-note">Solo números.</p>
    <div class="ctrl">
        <input id="telefono" name="telefono" class="phone-input" inputmode="tel" value="{{ old('telefono', $cliente?->telefono) }}">
    </div>
    <x-input-error :messages="$errors->get('telefono')" />
</div>

<div class="form-field">
    <label for="direccion">Dirección</label>
    <div class="ctrl">
        <input id="direccion" name="direccion" value="{{ old('direccion', $cliente?->direccion) }}">
    </div>
    <x-input-error :messages="$errors->get('direccion')" />
</div>

<div class="form-field">
    <label for="trabajo">Trabajo</label>
    <div class="ctrl">
        <input id="trabajo" name="trabajo" value="{{ old('trabajo', $cliente?->trabajo) }}">
    </div>
    <x-input-error :messages="$errors->get('trabajo')" />
</div>

<div class="form-field">
    <label>Foto de la cédula</label>
    <p class="form-note">Podés tomar la foto o subirla de la galería.</p>
    <div class="cedula-grid">
        <div>
            <label class="upl-rect-wrap">
                <div class="upl-rect {{ $fotoFrente ? 'has' : '' }}"
                     id="preview-cedula_foto_frente"
                     @if($fotoFrente) style="background-image: url('{{ asset('storage/'.$fotoFrente) }}')" @endif>
                    <span class="ph" @if($fotoFrente) style="display:none" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2.5"/><circle cx="12" cy="13.5" r="3.4"/><path d="M8.5 7l1.2-2.2h4.6L15.5 7"/></svg>
                        <small>Frente</small>
                    </span>
                </div>
                <input id="cedula_foto_frente" type="file" name="cedula_foto_frente" accept="image/*" hidden>
            </label>
            <x-input-error :messages="$errors->get('cedula_foto_frente')" />
        </div>
        <div>
            <label class="upl-rect-wrap">
                <div class="upl-rect {{ $fotoAtras ? 'has' : '' }}"
                     id="preview-cedula_foto_atras"
                     @if($fotoAtras) style="background-image: url('{{ asset('storage/'.$fotoAtras) }}')" @endif>
                    <span class="ph" @if($fotoAtras) style="display:none" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2.5"/><circle cx="12" cy="13.5" r="3.4"/><path d="M8.5 7l1.2-2.2h4.6L15.5 7"/></svg>
                        <small>Atrás</small>
                    </span>
                </div>
                <input id="cedula_foto_atras" type="file" name="cedula_foto_atras" accept="image/*" hidden>
            </label>
            <x-input-error :messages="$errors->get('cedula_foto_atras')" />
        </div>
    </div>
</div>
