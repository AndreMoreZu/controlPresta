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

            <div class="form-field">
                <label for="nombre">Nombre</label>
                <div class="ctrl">
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autofocus>
                </div>
                <x-input-error :messages="$errors->get('nombre')" />
            </div>

            <div class="form-field">
                <label for="apellidos">Apellidos</label>
                <div class="ctrl">
                    <input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" required>
                </div>
                <x-input-error :messages="$errors->get('apellidos')" />
            </div>

            <div class="form-field">
                <label for="cedula">Cédula</label>
                <div class="ctrl">
                    <input id="cedula" name="cedula" value="{{ old('cedula') }}">
                </div>
                <x-input-error :messages="$errors->get('cedula')" />
            </div>

            <div class="form-field">
                <label for="telefono">Teléfono</label>
                <div class="ctrl">
                    <input id="telefono" name="telefono" class="phone-input" inputmode="tel" value="{{ old('telefono') }}">
                </div>
                <x-input-error :messages="$errors->get('telefono')" />
            </div>

            <div class="form-field">
                <label for="direccion">Dirección</label>
                <div class="ctrl">
                    <input id="direccion" name="direccion" value="{{ old('direccion') }}">
                </div>
                <x-input-error :messages="$errors->get('direccion')" />
            </div>

            <div class="form-field">
                <label for="trabajo">Trabajo</label>
                <div class="ctrl">
                    <input id="trabajo" name="trabajo" value="{{ old('trabajo') }}">
                </div>
                <x-input-error :messages="$errors->get('trabajo')" />
            </div>

            <div class="form-field">
                <label>Foto de la cédula</label>
                <div class="cedula-grid">
                    <div>
                        <label class="upl-rect-wrap">
                            <div class="upl-rect" id="preview-cedula_foto_frente">
                                <span class="ph">
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
                            <div class="upl-rect" id="preview-cedula_foto_atras">
                                <span class="ph">
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

                <p class="form-note">Si lo dejás vacío, se asume igual al monto.</p>
                <div class="form-field">
                    <label for="saldo">Saldo que debe hoy</label>
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
                    <p class="form-note">Poné la fecha de la primera cuota que no pagó; el sistema calculará los días de multa más adelante.</p>

                    <div class="form-field">
                        <label for="atraso_desde">Fecha de la cuota que no pagó</label>
                        <div class="ctrl"><input id="atraso_desde" type="date" name="atraso_desde" value="{{ old('atraso_desde') }}"></div>
                        <x-input-error :messages="$errors->get('atraso_desde')" />
                    </div>

                    <div class="form-field">
                        <label for="multa_acumulada">Multa acumulada</label>
                        <div class="ctrl"><span class="pre">₡</span><input id="multa_acumulada" name="multa_acumulada" class="money-input" inputmode="numeric" value="{{ old('multa_acumulada') }}"></div>
                        <x-input-error :messages="$errors->get('multa_acumulada')" />
                    </div>

                    <div class="form-field">
                        <label for="intereses_atrasados">Intereses atrasados (sin pagar)</label>
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
