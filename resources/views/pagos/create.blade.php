<x-app-layout>
    <x-slot name="header">
        Registrar pago
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    <div class="form-container" style="max-width: 520px;">
        <form id="form-pago" method="POST" action="{{ route('pagos.store', $cliente) }}">
            @csrf

            {{-- ── Desglose del cobro: siempre visible ─────────────────────────── --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Desglose del cobro
                    <span class="sub">Editá los montos si hace falta</span>
                </h3>

                <div class="pago-tabla-head">
                    <span>Concepto</span>
                    <span>Adeuda</span>
                    <span>Paga hoy</span>
                </div>

                {{-- Interés del período: SIEMPRE visible y editable --}}
                @php $interesPrecargado = $cobrarInteres ? $interes : 0; @endphp
                <div class="pago-fila" id="fila-interes">
                    <span class="pago-concepto">Interés del período</span>
                    <span class="pago-adeuda">{{ colones($interes) }}</span>
                    <div class="pago-input-wrap">
                        <span class="pre">₡</span>
                        <input type="text"
                               id="disp-interes"
                               class="pago-input concepto-input"
                               inputmode="numeric"
                               autocomplete="off"
                               data-max="{{ $interes }}"
                               value="{{ number_format(old('pago_interes', $interesPrecargado), 0, '.', '.') }}">
                        <input type="hidden"
                               name="pago_interes"
                               id="pago_interes"
                               value="{{ old('pago_interes', $interesPrecargado) }}">
                    </div>
                </div>
                <x-input-error :messages="$errors->get('pago_interes')" class="field-error" />

                {{-- Multa por atraso (si hay) --}}
                @if ($multa > 0)
                    <div class="pago-fila">
                        <span class="pago-concepto pago-concepto-multa">Multa por atraso</span>
                        <span class="pago-adeuda pago-adeuda-multa">{{ colones($multa) }}</span>
                        <div class="pago-input-wrap">
                            <span class="pre">₡</span>
                            <input type="text"
                                   id="disp-multa"
                                   class="pago-input concepto-input"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   data-max="{{ $multa }}"
                                   value="{{ number_format(old('pago_multa', $multa), 0, '.', '.') }}">
                            <input type="hidden"
                                   name="pago_multa"
                                   id="pago_multa"
                                   value="{{ old('pago_multa', $multa) }}">
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('pago_multa')" class="field-error" />
                @else
                    <input type="hidden" name="pago_multa" value="0">
                @endif

                {{-- Intereses atrasados (si hay) --}}
                @if ($interesesAtr > 0)
                    <div class="pago-fila">
                        <span class="pago-concepto pago-concepto-atr">Intereses atrasados</span>
                        <span class="pago-adeuda pago-adeuda-atr">{{ colones($interesesAtr) }}</span>
                        <div class="pago-input-wrap">
                            <span class="pre">₡</span>
                            <input type="text"
                                   id="disp-intereses-atr"
                                   class="pago-input concepto-input"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   data-max="{{ $interesesAtr }}"
                                   value="{{ number_format(old('pago_intereses_atr', $interesesAtr), 0, '.', '.') }}">
                            <input type="hidden"
                                   name="pago_intereses_atr"
                                   id="pago_intereses_atr"
                                   value="{{ old('pago_intereses_atr', $interesesAtr) }}">
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('pago_intereses_atr')" class="field-error" />
                @else
                    <input type="hidden" name="pago_intereses_atr" value="0">
                @endif
            </div>

            {{-- ── Fecha del próximo cobro: siempre visible ─────────────────────── --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Fecha del próximo cobro
                    <span class="sub">La que acordaron con el cliente</span>
                </h3>
                <div class="form-field" style="margin: 0;">
                    <div class="ctrl">
                        <input type="date"
                               id="proximo_cobro"
                               name="proximo_cobro"
                               value="{{ old('proximo_cobro', $proximoSugerido) }}">
                    </div>
                    <x-input-error :messages="$errors->get('proximo_cobro')" class="field-error" />
                </div>
            </div>

            {{-- ── Abono al capital (siempre visible) ──────────────────────────── --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Abono al capital
                    <span class="sub">Opcional — baja la deuda</span>
                </h3>
                <div class="pago-fila pago-fila-abono">
                    <span class="pago-concepto">Abono</span>
                    <span class="pago-adeuda" style="color: var(--muted);">{{ colones($prestamo->saldo) }}</span>
                    <div class="pago-input-wrap">
                        <span class="pre">₡</span>
                        <input type="text"
                               id="disp-abono"
                               class="pago-input concepto-input"
                               inputmode="numeric"
                               autocomplete="off"
                               data-max="{{ $prestamo->saldo }}"
                               placeholder="0"
                               value="{{ old('abono') ? number_format((int) old('abono'), 0, '.', '.') : '' }}">
                        <input type="hidden"
                               name="abono"
                               id="abono"
                               value="{{ old('abono', 0) }}">
                    </div>
                </div>
                <x-input-error :messages="$errors->get('abono')" class="field-error" />
            </div>

            {{-- ── Total del pago (live con JS) ────────────────────────────────── --}}
            <div class="panel pago-total-panel" style="margin-bottom: 14px;">
                <h3 style="color: var(--accent-dark);">Total del pago</h3>
                <div class="pago-total-row">
                    <span class="pago-currency">₡</span>
                    <span id="total-display" class="pago-total-num">
                        {{ number_format($interesPrecargado + $multa + $interesesAtr, 0, '.', '.') }}
                    </span>
                </div>
            </div>

            {{-- ── Método de pago ───────────────────────────────────────────────── --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Método de pago</h3>
                <div class="freq-grid">
                    <label class="freq-opt {{ old('metodo', 'efectivo') === 'efectivo' ? 'on' : '' }}">
                        <input type="radio" name="metodo" value="efectivo"
                               {{ old('metodo', 'efectivo') === 'efectivo' ? 'checked' : '' }}>
                        <b>Efectivo</b>
                        <span>En mano</span>
                    </label>
                    <label class="freq-opt {{ old('metodo') === 'sinpe' ? 'on' : '' }}">
                        <input type="radio" name="metodo" value="sinpe"
                               {{ old('metodo') === 'sinpe' ? 'checked' : '' }}>
                        <b>Sinpe</b>
                        <span>Móvil</span>
                    </label>
                    <label class="freq-opt {{ old('metodo') === 'transferencia' ? 'on' : '' }}">
                        <input type="radio" name="metodo" value="transferencia"
                               {{ old('metodo') === 'transferencia' ? 'checked' : '' }}>
                        <b>Transferencia</b>
                        <span>Banco</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('metodo')" class="field-error" style="margin-top: 8px;" />
            </div>

            <button type="submit" class="btn-save">Confirmar pago</button>
        </form>
    </div>

    <script>
    (function () {
        const interesMax    = {{ $interes }};
        const cobrarInteres = {{ $cobrarInteres ? 'true' : 'false' }};

        // ── Utilidades ────────────────────────────────────────────────────────
        function parsear(v) {
            return parseInt(String(v).replace(/\D/g, ''), 10) || 0;
        }

        function formatear(n) {
            return n > 0 ? n.toLocaleString('es-CR') : '';
        }

        // ── Vincular cada campo visible con su hidden ─────────────────────────
        document.querySelectorAll('.concepto-input').forEach(function (disp) {
            const max    = parseInt(disp.dataset.max, 10) || 0;
            const hidden = disp.nextElementSibling;

            disp.addEventListener('input', function () {
                const raw     = this.value.replace(/\D/g, '');
                const num     = parseInt(raw, 10) || 0;
                const val     = Math.min(num, max);
                const cursor  = this.selectionStart;
                const antes   = this.value.length;
                this.value    = formatear(val) || (num === 0 ? '' : formatear(val));
                const despues = this.value.length;
                this.setSelectionRange(
                    Math.max(0, cursor + (despues - antes)),
                    Math.max(0, cursor + (despues - antes))
                );
                hidden.value = val;
                recalcularTotal();
            });
        });

        // ── Recalcular total en vivo ──────────────────────────────────────────
        function recalcularTotal() {
            var total = 0;
            document.querySelectorAll('input[type="hidden"][name^="pago_"], input[type="hidden"][name="abono"]')
                .forEach(function (h) { total += parsear(h.value); });
            document.getElementById('total-display').textContent =
                total > 0 ? total.toLocaleString('es-CR') : '0';
        }

        // ── Alerta si el interés cobrado es menor al del período ──────────────
        // Solo cuando le toca cobrar interés ese día. El período se cierra igual.
        document.getElementById('form-pago').addEventListener('submit', function (e) {
            const ingresado = parseInt(document.getElementById('disp-interes').value.replace(/\D/g, ''), 10) || 0;
            if (cobrarInteres && interesMax > 0 && ingresado < interesMax) {
                const fmtIng = ingresado.toLocaleString('es-CR');
                const fmtMax = interesMax.toLocaleString('es-CR');
                const ok = confirm(
                    'Estás cobrando ₡' + fmtIng + ' de interés, ' +
                    'pero el interés del período es ₡' + fmtMax + '.\n' +
                    'El período se dará por cerrado y el cliente quedará al día.\n' +
                    '¿Continuar?'
                );
                if (!ok) { e.preventDefault(); }
            }
        });

        // ── Radio buttons de método ───────────────────────────────────────────
        document.querySelectorAll('.freq-opt input[type="radio"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.freq-opt').forEach(function (opt) {
                    opt.classList.remove('on');
                });
                this.closest('.freq-opt').classList.add('on');
            });
        });

        recalcularTotal();
    })();
    </script>
</x-app-layout>
