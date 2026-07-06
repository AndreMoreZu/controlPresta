<x-app-layout>
    <x-slot name="header">
        Nuevo cliente
    </x-slot>

    <a href="{{ route('clientes.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a clientes
    </a>

    <div class="panel form-container">
        <form method="POST" action="{{ route('clientes.store') }}" enctype="multipart/form-data" id="form-cliente">
            @csrf

            <h3>Datos del cliente</h3>

            @include('clientes._campos_cliente', ['cliente' => null])

            <div class="form-divider"></div>

            <label class="switch-row">
                <input type="checkbox" id="tiene_prestamo" name="tiene_prestamo" value="1" {{ old('tiene_prestamo') ? 'checked' : '' }}>
                <span>
                    ¿Ya tiene un préstamo activo?<br>
                    <small style="font-weight: 400; color: var(--muted);">Para clientes que vienen del cuaderno. Si es nuevo y todavía no le has prestado, dejalo apagado.</small>
                </span>
            </label>

            <div id="prestamo-box" class="hidden-box {{ old('tiene_prestamo') ? 'show' : '' }}">
                <div class="form-field">
                    <label for="monto">Monto original del préstamo</label>
                    <div class="ctrl"><span class="pre">₡</span><input id="monto" name="monto" class="money-input" inputmode="numeric" value="{{ old('monto') }}"></div>
                    <x-input-error :messages="$errors->get('monto')" />
                </div>

                <div class="form-field">
                    <label for="saldo">Saldo que debe hoy</label>
                    <p class="form-note">Si lo dejás vacío, se toma igual al monto.</p>
                    <div class="ctrl"><span class="pre">₡</span><input id="saldo" name="saldo" class="money-input" inputmode="numeric" value="{{ old('saldo') }}"></div>
                    <x-input-error :messages="$errors->get('saldo')" />
                </div>

                <div class="form-field">
                    <label>Frecuencia de pago</label>
                    <div class="freq-grid">
                        <label class="freq-opt {{ old('frecuencia', 'mensual') === 'mensual' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="mensual" {{ old('frecuencia', 'mensual') === 'mensual' ? 'checked' : '' }}>
                            <b>Mensual</b><span>20%</span>
                        </label>
                        <label class="freq-opt {{ old('frecuencia') === 'quincenal' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="quincenal" {{ old('frecuencia') === 'quincenal' ? 'checked' : '' }}>
                            <b>Quincenal</b><span>15%</span>
                        </label>
                        <label class="freq-opt {{ old('frecuencia') === 'semanal' ? 'on' : '' }}">
                            <input type="radio" name="frecuencia" value="semanal" {{ old('frecuencia') === 'semanal' ? 'checked' : '' }}>
                            <b>Semanal</b><span>5%</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('frecuencia')" />
                </div>

                <div class="form-field">
                    <label for="interes_pagados">Intereses que ya ha pagado</label>
                    <p class="form-note">Lo que ya pagó de intereses, según tu cuaderno.</p>
                    <div class="ctrl"><span class="pre">₡</span><input id="interes_pagados" name="interes_pagados" class="money-input" inputmode="numeric" value="{{ old('interes_pagados') }}"></div>
                    <x-input-error :messages="$errors->get('interes_pagados')" />
                </div>

                <div class="form-field">
                    <label for="inicio">Fecha en que se le prestó</label>
                    <div class="ctrl"><input id="inicio" type="date" name="inicio" value="{{ old('inicio') }}"></div>
                    <x-input-error :messages="$errors->get('inicio')" />
                </div>

                <div class="form-field">
                    <label for="proximo">Fecha del próximo cobro</label>
                    <p class="form-note">La fecha que acordaron para el siguiente pago (de aquí el sistema sabe si paga los 15, los 30, etc.).</p>
                    <div class="ctrl"><input id="proximo" type="date" name="proximo" value="{{ old('proximo') }}"></div>
                    <x-input-error :messages="$errors->get('proximo')" />
                </div>

                <label class="switch-row">
                    <input type="checkbox" id="tiene_atraso" name="tiene_atraso" value="1" {{ old('tiene_atraso') ? 'checked' : '' }}>
                    <span>
                        ¿Está atrasado?<br>
                        <small style="font-weight: 400; color: var(--muted);">Apagado = está al día. Activalo solo si ya debe pagos.</small>
                    </span>
                </label>

                <div id="atraso-box" class="hidden-box {{ old('tiene_atraso') ? 'show' : '' }}">
                    <div class="form-field">
                        <label for="atraso_desde">Fecha de la cuota que no pagó</label>
                        <p class="form-note">La primera fecha que debía pagar y no pagó.</p>
                        <div class="ctrl"><input id="atraso_desde" type="date" name="atraso_desde" value="{{ old('atraso_desde') }}"></div>
                        <x-input-error :messages="$errors->get('atraso_desde')" />
                    </div>

                    <div class="form-field">
                        <label for="multa_acumulada">Multa acumulada</label>
                        <p class="form-note">La multa que ya lleva, según tu cuaderno.</p>
                        <div class="ctrl"><span class="pre">₡</span><input id="multa_acumulada" name="multa_acumulada" class="money-input" inputmode="numeric" value="{{ old('multa_acumulada') }}"></div>
                        <x-input-error :messages="$errors->get('multa_acumulada')" />
                    </div>

                    <div class="form-field">
                        <label for="intereses_atrasados">Intereses atrasados (sin pagar)</label>
                        <p class="form-note">Intereses sin pagar que ya lleva acumulados.</p>
                        <div class="ctrl"><span class="pre">₡</span><input id="intereses_atrasados" name="intereses_atrasados" class="money-input" inputmode="numeric" value="{{ old('intereses_atrasados') }}"></div>
                        <x-input-error :messages="$errors->get('intereses_atrasados')" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">Crear cliente</button>
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
        vincularInterruptor('tiene_atraso', 'atraso-box');

        document.querySelectorAll('.freq-opt').forEach((opt) => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.freq-opt').forEach((o) => o.classList.remove('on'));
                opt.classList.add('on');
            });
        });

        function vincularPreviewFoto(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                if (input.files[0]) {
                    preview.classList.add('has');
                    preview.style.backgroundImage = `url('${URL.createObjectURL(input.files[0])}')`;
                }
            });
        }
        vincularPreviewFoto('cedula_foto_frente', 'preview-cedula_foto_frente');
        vincularPreviewFoto('cedula_foto_atras', 'preview-cedula_foto_atras');

        // Formato visual de teléfono (espacio después del 4° dígito) y montos (puntos de miles).
        // Lo que se guarda en la base es siempre el dígito pelado: el formato se limpia al enviar el formulario.
        function soloDigitos(valor) {
            return (valor || '').replace(/\D/g, '');
        }

        function formatearTelefono(valor) {
            const digitos = soloDigitos(valor);
            return digitos.length > 4 ? `${digitos.slice(0, 4)} ${digitos.slice(4, 8)}` : digitos;
        }

        function formatearMiles(valor) {
            const digitos = soloDigitos(valor);
            return digitos.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        document.querySelectorAll('.phone-input').forEach((input) => {
            input.value = formatearTelefono(input.value);
            input.addEventListener('input', () => {
                input.value = formatearTelefono(input.value);
            });
        });

        document.querySelectorAll('.money-input').forEach((input) => {
            input.value = formatearMiles(input.value);
            input.addEventListener('input', () => {
                input.value = formatearMiles(input.value);
            });
        });

        document.getElementById('form-cliente').addEventListener('submit', () => {
            document.querySelectorAll('.phone-input, .money-input').forEach((input) => {
                input.value = soloDigitos(input.value);
            });
        });
    </script>
</x-app-layout>
