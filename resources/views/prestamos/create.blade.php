<x-app-layout>
    <x-slot name="header">
        Nuevo préstamo
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    <div class="panel form-container">
        <form method="POST" action="{{ route('prestamos.store', $cliente) }}" id="form-prestamo">
            @csrf

            <h3>Datos del préstamo</h3>

            <div class="form-field">
                <label for="monto">Monto a prestar</label>
                <div class="ctrl">
                    <span class="pre">₡</span>
                    <input id="monto" name="monto" class="money-input" inputmode="numeric"
                           autocomplete="off" value="{{ old('monto') }}">
                </div>
                <x-input-error :messages="$errors->get('monto')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="frecuencia">Frecuencia de cobro</label>
                <div class="ctrl">
                    <select id="frecuencia" name="frecuencia" id="frec-select">
                        <option value="">— Elegí una —</option>
                        @foreach (['mensual' => 'Mensual (20%)', 'quincenal' => 'Quincenal (15%)', 'semanal' => 'Semanal (5%)'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('frecuencia') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input-error :messages="$errors->get('frecuencia')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="inicio">Fecha en que se presta</label>
                <div class="ctrl">
                    <input type="date" id="inicio" name="inicio"
                           value="{{ old('inicio', today()->toDateString()) }}">
                </div>
                <x-input-error :messages="$errors->get('inicio')" class="field-error" />
            </div>

            <div class="form-field">
                <label for="proximo">Fecha del primer cobro</label>
                <p class="form-note">Se calcula según la frecuencia, pero podés cambiarla.</p>
                <div class="ctrl">
                    <input type="date" id="proximo" name="proximo"
                           value="{{ old('proximo', today()->toDateString()) }}">
                </div>
                <x-input-error :messages="$errors->get('proximo')" class="field-error" />
            </div>

            <div class="form-divider"></div>

            <button type="submit" class="btn-primary-full">Crear préstamo</button>
        </form>
    </div>

    <script>
    // ── Separador de miles en el campo monto ──────────────────────────────────
    (function () {
        function soloDigitos(v) { return (v || '').replace(/\D/g, ''); }
        function formatearMiles(v) { return soloDigitos(v).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

        document.querySelectorAll('.money-input').forEach(function (input) {
            input.value = formatearMiles(input.value);
            input.addEventListener('input', function () { this.value = formatearMiles(this.value); });
        });

        document.getElementById('form-prestamo').addEventListener('submit', function () {
            document.querySelectorAll('.money-input').forEach(function (input) {
                input.value = soloDigitos(input.value);
            });
        });
    })();

    // Pre-sugerir fecha del primer cobro según frecuencia elegida
    (function () {
        const frecSelect = document.getElementById('frecuencia');
        const proxInput  = document.getElementById('proximo');
        const inicioInput = document.getElementById('inicio');

        function sugerirProximo() {
            const frec = frecSelect.value;
            if (!frec) return;

            const base = inicioInput.value ? new Date(inicioInput.value + 'T00:00:00') : new Date();
            let sugerida = new Date(base);

            if (frec === 'mensual') {
                sugerida.setMonth(sugerida.getMonth() + 1);
            } else if (frec === 'quincenal') {
                const dia = base.getDate();
                if (dia <= 15) {
                    sugerida.setDate(Math.min(30, new Date(base.getFullYear(), base.getMonth() + 1, 0).getDate()));
                } else {
                    sugerida.setMonth(sugerida.getMonth() + 1);
                    sugerida.setDate(15);
                }
            } else if (frec === 'semanal') {
                sugerida.setDate(sugerida.getDate() + 7);
            }

            // Formato YYYY-MM-DD
            const yyyy = sugerida.getFullYear();
            const mm   = String(sugerida.getMonth() + 1).padStart(2, '0');
            const dd   = String(sugerida.getDate()).padStart(2, '0');
            proxInput.value = `${yyyy}-${mm}-${dd}`;
        }

        // Solo sugerir si el campo proximo no fue editado manualmente por el usuario
        // o si aún tiene el valor por defecto (hoy). Si ya hay un old() de error de
        // validación, no sobreescribir.
        frecSelect.addEventListener('change', sugerirProximo);
        inicioInput.addEventListener('change', function () {
            if (frecSelect.value) sugerirProximo();
        });
    })();
    </script>
</x-app-layout>
