<x-app-layout>
    <x-slot name="header">
        Editar cliente
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a la ficha
    </a>

    <div class="panel form-container">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}" enctype="multipart/form-data" id="form-cliente">
            @csrf
            @method('PUT')

            {{--
                Estado inicial de los interruptores:
                  - Si hay old() (reenvío tras error de validación), se respeta lo que el usuario envió.
                  - Si es carga fresca, se usa el flag del controller ($tienePrestamo / $estaAtrasado).
            --}}
            @php
                $checkTienePrestamo = old('tiene_prestamo', $tienePrestamo ? '1' : null);
                $checkEstaAtrasado  = old('tiene_atraso',   $estaAtrasado  ? '1' : null);
            @endphp

            <h3>Datos del cliente</h3>

            @include('clientes._campos_cliente', ['cliente' => $cliente])

            <div class="form-divider"></div>

            <label class="switch-row">
                <input type="checkbox" id="tiene_prestamo" name="tiene_prestamo" value="1"
                       {{ $checkTienePrestamo ? 'checked' : '' }}>
                <span>
                    ¿Tiene préstamo activo?<br>
                    <small style="font-weight: 400; color: var(--muted);">Activalo para ver y corregir los datos del préstamo.</small>
                </span>
            </label>

            <div id="prestamo-box" class="hidden-box {{ $checkTienePrestamo ? 'show' : '' }}">

                <div class="form-field">
                    <label for="monto">Monto original del préstamo</label>
                    <div class="ctrl"><span class="pre">₡</span><input id="monto" name="monto" class="money-input" inputmode="numeric" value="{{ old('monto', $prestamo?->monto) }}"></div>
                    <x-input-error :messages="$errors->get('monto')" />
                </div>

                <div class="form-field">
                    <label for="saldo">Saldo que debe hoy</label>
                    <p class="form-note">Si lo dejás vacío, se toma igual al monto.</p>
                    <div class="ctrl"><span class="pre">₡</span><input id="saldo" name="saldo" class="money-input" inputmode="numeric" value="{{ old('saldo', $prestamo?->saldo) }}"></div>
                    <x-input-error :messages="$errors->get('saldo')" />
                </div>

                <div class="form-field">
                    <label>Frecuencia de pago</label>
                    <div class="freq-grid">
                        <label class="freq-opt {{ old('frecuencia', $prestamo?->frecuencia) === 'mensual' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="mensual"
                                   {{ old('frecuencia', $prestamo?->frecuencia) === 'mensual' ? 'checked' : '' }}>
                            <b>Mensual</b><span>20%</span>
                        </label>
                        <label class="freq-opt {{ old('frecuencia', $prestamo?->frecuencia) === 'quincenal' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="quincenal"
                                   {{ old('frecuencia', $prestamo?->frecuencia) === 'quincenal' ? 'checked' : '' }}>
                            <b>Quincenal</b><span>10%</span>
                        </label>
                        <label class="freq-opt {{ old('frecuencia', $prestamo?->frecuencia) === 'semanal' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="semanal"
                                   {{ old('frecuencia', $prestamo?->frecuencia) === 'semanal' ? 'checked' : '' }}>
                            <b>Semanal</b><span>5%</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('frecuencia')" />
                </div>

                <div class="form-field">
                    <label for="interes_pagados">Intereses que ya ha pagado</label>
                    <p class="form-note">Lo que ya pagó de intereses, según tu cuaderno.</p>
                    <div class="ctrl"><span class="pre">₡</span><input id="interes_pagados" name="interes_pagados" class="money-input" inputmode="numeric" value="{{ old('interes_pagados', $prestamo?->interes_pagados) }}"></div>
                    <x-input-error :messages="$errors->get('interes_pagados')" />
                </div>

                <div class="form-field">
                    <label for="inicio">Fecha en que se le prestó</label>
                    <div class="ctrl"><input id="inicio" type="date" name="inicio" value="{{ old('inicio', $prestamo?->inicio?->format('Y-m-d')) }}"></div>
                    <x-input-error :messages="$errors->get('inicio')" />
                </div>

                <div class="form-field">
                    <label for="proximo">Fecha del próximo cobro</label>
                    <p class="form-note">La fecha que acordaron para el siguiente pago.</p>
                    <div class="ctrl"><input id="proximo" type="date" name="proximo" value="{{ old('proximo', $prestamo?->proximo?->format('Y-m-d')) }}"></div>
                    <x-input-error :messages="$errors->get('proximo')" />
                </div>

                <label class="switch-row">
                    <input type="checkbox" id="tiene_atraso" name="tiene_atraso" value="1"
                           {{ $checkEstaAtrasado ? 'checked' : '' }}>
                    <span>
                        ¿Está atrasado?<br>
                        <small style="font-weight: 400; color: var(--muted);">Apagado = está al día. Activalo para corregir datos de atraso.</small>
                    </span>
                </label>

                <div id="atraso-box" class="hidden-box {{ $checkEstaAtrasado ? 'show' : '' }}">

                    <div class="form-field">
                        <label for="atraso_desde">Fecha de la cuota que no pagó</label>
                        <p class="form-note">La primera fecha que debía pagar y no pagó. Dejá vacío si está al día.</p>
                        <div class="ctrl"><input id="atraso_desde" type="date" name="atraso_desde" value="{{ old('atraso_desde', $prestamo?->atraso_desde?->format('Y-m-d')) }}"></div>
                        <x-input-error :messages="$errors->get('atraso_desde')" />
                    </div>

                    <div class="form-field">
                        <label for="multa_acumulada">Multa acumulada</label>
                        <p class="form-note">La multa que ya lleva, según tu cuaderno.</p>
                        <div class="ctrl"><span class="pre">₡</span><input id="multa_acumulada" name="multa_acumulada" class="money-input" inputmode="numeric" value="{{ old('multa_acumulada', $prestamo?->multa_acumulada) }}"></div>
                        <x-input-error :messages="$errors->get('multa_acumulada')" />
                    </div>

                    <div class="form-field">
                        <label for="intereses_atrasados">Intereses atrasados (sin pagar)</label>
                        <p class="form-note">Intereses sin pagar que ya lleva acumulados.</p>
                        <div class="ctrl"><span class="pre">₡</span><input id="intereses_atrasados" name="intereses_atrasados" class="money-input" inputmode="numeric"
                            value="{{ old('intereses_atrasados', $prestamo ? $prestamo->interesesAtrasados->where('pagado', false)->sum('monto') : '') }}"></div>
                        <x-input-error :messages="$errors->get('intereses_atrasados')" />
                    </div>

                </div>{{-- /#atraso-box --}}

            </div>{{-- /#prestamo-box --}}

            <button type="submit" class="btn-save">Guardar cambios</button>
        </form>
    </div>

    <script>
        function vincularInterruptor(checkboxId, boxId) {
            const checkbox = document.getElementById(checkboxId);
            const caja = document.getElementById(boxId);
            if (!checkbox || !caja) return;
            checkbox.addEventListener('change', () => caja.classList.toggle('show', checkbox.checked));
        }
        vincularInterruptor('tiene_prestamo', 'prestamo-box');
        vincularInterruptor('tiene_atraso',   'atraso-box');

        document.querySelectorAll('.freq-opt').forEach((opt) => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.freq-opt').forEach((o) => o.classList.remove('on'));
                opt.classList.add('on');
            });
        });

        function vincularPreviewFoto(inputId, previewId) {
            const input   = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                if (input.files[0]) {
                    preview.classList.add('has');
                    preview.style.backgroundImage = `url('${URL.createObjectURL(input.files[0])}')`;
                    const ph = preview.querySelector('.ph');
                    if (ph) ph.style.display = 'none';
                }
            });
        }
        vincularPreviewFoto('cedula_foto_frente', 'preview-cedula_foto_frente');
        vincularPreviewFoto('cedula_foto_atras',  'preview-cedula_foto_atras');

        function soloDigitos(valor) {
            return (valor || '').replace(/\D/g, '');
        }
        function formatearTelefono(valor) {
            const d = soloDigitos(valor);
            return d.length > 4 ? `${d.slice(0, 4)} ${d.slice(4, 8)}` : d;
        }
        function formatearMiles(valor) {
            return soloDigitos(valor).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        document.querySelectorAll('.phone-input').forEach((input) => {
            input.value = formatearTelefono(input.value);
            input.addEventListener('input', () => { input.value = formatearTelefono(input.value); });
        });

        document.querySelectorAll('.money-input').forEach((input) => {
            input.value = formatearMiles(input.value);
            input.addEventListener('input', () => { input.value = formatearMiles(input.value); });
        });

        document.getElementById('form-cliente').addEventListener('submit', () => {
            document.querySelectorAll('.phone-input, .money-input').forEach((input) => {
                input.value = soloDigitos(input.value);
            });
        });
    </script>
</x-app-layout>
