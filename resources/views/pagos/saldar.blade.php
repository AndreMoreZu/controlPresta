<x-app-layout>
    <x-slot name="header">
        Saldar cuenta
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    <div class="form-container" style="max-width: 520px;">
        <form method="POST" action="{{ route('pagos.saldar.store', $cliente) }}">
            @csrf

            {{-- Desglose editable --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Desglose de la cuenta
                    <span class="sub">Capital siempre incluido — editá el resto si hay ajuste</span>
                </h3>

                <div class="pago-tabla-head">
                    <span>Concepto</span>
                    <span>Adeuda</span>
                    <span>Cobra hoy</span>
                </div>

                {{-- Capital: fijo, no editable --}}
                <input type="hidden" name="pago_capital" value="{{ $prestamo->saldo }}">
                <div class="pago-fila">
                    <span class="pago-concepto">Capital (saldo)</span>
                    <span class="pago-adeuda">{{ colones($prestamo->saldo) }}</span>
                    <span style="min-width:130px; text-align:right; font-weight:800;
                                 color:var(--accent-dark); font-size:14px; padding:4px 0;">
                        {{ colones($prestamo->saldo) }}
                    </span>
                </div>

                {{-- Interés del período: SIEMPRE visible --}}
                {{-- default = interesPeriodo() calculado; ₡0 si no hay interés acumulado. --}}
                {{-- El dueño puede editarlo para cobrar el período al saldar anticipado.   --}}
                <div class="pago-fila">
                    <span class="pago-concepto">
                        Interés del período
                        @if ($prestamo->interes_pendiente > 0)
                            <small class="pago-nota-concepto">(pendiente)</small>
                        @endif
                    </span>
                    <span class="pago-adeuda">{{ colones($interes) }}</span>
                    <div class="pago-input-wrap">
                        <span class="pre">₡</span>
                        <input type="text"
                               id="disp-interes"
                               class="pago-input concepto-input"
                               inputmode="numeric"
                               autocomplete="off"
                               data-max="{{ $interes }}"
                               value="{{ number_format(old('pago_interes', $interes), 0, '.', '.') }}">
                        <input type="hidden"
                               name="pago_interes"
                               id="pago_interes"
                               value="{{ old('pago_interes', $interes) }}">
                    </div>
                </div>

                {{-- Intereses atrasados: SIEMPRE visible (₡0 si no hay) --}}
                <div class="pago-fila">
                    <span class="pago-concepto pago-concepto-atr">Intereses atrasados</span>
                    <span class="pago-adeuda pago-adeuda-atr">{{ colones($atrasados) }}</span>
                    <div class="pago-input-wrap">
                        <span class="pre">₡</span>
                        <input type="text"
                               id="disp-intereses-atr"
                               class="pago-input concepto-input"
                               inputmode="numeric"
                               autocomplete="off"
                               data-max="{{ $atrasados }}"
                               value="{{ number_format(old('pago_intereses_atr', $atrasados), 0, '.', '.') }}">
                        <input type="hidden"
                               name="pago_intereses_atr"
                               id="pago_intereses_atr"
                               value="{{ old('pago_intereses_atr', $atrasados) }}">
                    </div>
                </div>

                {{-- Multa: SIEMPRE visible (₡0 si no hay) --}}
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
            </div>

            {{-- Total en vivo --}}
            <div class="panel pago-total-panel" style="margin-bottom: 14px;">
                <h3 style="color: var(--accent-dark);">Total a cobrar</h3>
                <div class="pago-total-row">
                    <span class="pago-currency">₡</span>
                    <span id="total-display" class="pago-total-num">
                        {{ number_format($total, 0, '.', '.') }}
                    </span>
                </div>
            </div>

            {{-- Método de pago --}}
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
            </div>

            <div class="panel" style="margin-bottom: 14px; background: #fdf3f3; border-color: var(--red);">
                <p style="font-size: 13px; color: #8b1a1a; margin: 0;">
                    Esta acción cierra el préstamo definitivamente.
                    El cliente quedará en estado <strong>Sin préstamo</strong>.
                    No se puede deshacer.
                </p>
            </div>

            <button type="submit" class="btn-primary-full" style="background: var(--red); margin-top: 14px;">
                Confirmar — Saldar ₡<span id="btn-total">{{ number_format($total, 0, '.', '.') }}</span>
            </button>
        </form>
    </div>

    <script>
    (function () {
        function parsear(v) {
            return parseInt(String(v).replace(/\D/g, ''), 10) || 0;
        }

        function formatear(n) {
            return n > 0 ? n.toLocaleString('es-CR') : '';
        }

        // Vincular cada campo visible con su hidden (mismo patrón que pagos/create)
        document.querySelectorAll('.concepto-input').forEach(function (disp) {
            const max    = parseInt(disp.dataset.max, 10) || 0;
            const hidden = disp.nextElementSibling;

            disp.addEventListener('input', function () {
                const raw    = this.value.replace(/\D/g, '');
                const num    = parseInt(raw, 10) || 0;
                const val    = Math.min(num, max);
                const cursor = this.selectionStart;
                const antes  = this.value.length;
                this.value   = formatear(val) || (num === 0 ? '' : formatear(val));
                const despues = this.value.length;
                this.setSelectionRange(
                    Math.max(0, cursor + (despues - antes)),
                    Math.max(0, cursor + (despues - antes))
                );
                hidden.value = val;
                recalcularTotal();
            });
        });

        // Capital fijo + suma de conceptos editables
        function recalcularTotal() {
            var total = 0;
            document.querySelectorAll('input[type="hidden"][name^="pago_"]').forEach(function (h) {
                total += parsear(h.value);
            });
            const fmt = total > 0 ? total.toLocaleString('es-CR') : '0';
            document.getElementById('total-display').textContent = fmt;
            document.getElementById('btn-total').textContent     = fmt;
        }

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
