<x-app-layout>
    <x-slot name="header">
        Registrar pago
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    <div class="form-container" style="max-width: 480px;">
        <form method="POST" action="{{ route('pagos.store', $cliente) }}">
            @csrf

            @if ($cobrarInteres)
                {{-- ── Flujo A: cobro vencido — mostrar desglose completo ─────────── --}}
                <div class="panel" style="margin-bottom: 14px;">
                    <h3>Resumen del cobro</h3>
                    <div class="kv">
                        <span class="k">Interés del período</span>
                        <span class="v interes">{{ colones($interes) }}</span>
                    </div>
                    @if ($multa > 0)
                        <div class="kv">
                            <span class="k">Multa por atraso</span>
                            <span class="v" style="color: var(--red);">{{ colones($multa) }}</span>
                        </div>
                    @endif
                    @if ($interesesAtr > 0)
                        <div class="kv">
                            <span class="k">Intereses no pagados</span>
                            <span class="v" style="color: #b47d3a;">{{ colones($interesesAtr) }}</span>
                        </div>
                    @endif
                    <div class="kv" style="border-top: 2px solid var(--line); padding-top: 12px; margin-top: 4px;">
                        <span class="k" style="font-weight: 700;">Total mínimo</span>
                        <span class="v" style="font-size: 18px; font-weight: 800; color: var(--accent-dark);">{{ colones($minimo) }}</span>
                    </div>
                </div>
            @else
                {{-- ── Flujo B: cobro adelantado — banner informativo ──────────────── --}}
                <div class="pago-adelantado">
                    <strong>Este cliente ya está al día</strong>
                    Su próximo interés se cobra el {{ $prestamo->proximo?->format('d/m/Y') ?? '—' }}.
                    Solo podés registrar un abono al capital si el cliente quiere adelantar pago de deuda.
                </div>
            @endif

            {{-- Abono al capital (siempre visible) --}}
            <div class="panel" style="margin-bottom: 14px;">
                <h3>Abono al capital
                    @if ($cobrarInteres)
                        <span class="sub">Opcional — si el cliente quiere bajar la deuda</span>
                    @else
                        <span class="sub">Opcional — adelanto sobre el saldo de la deuda</span>
                    @endif
                </h3>
                <div class="form-field" style="margin-bottom: 4px;">
                    <label for="abono-display">Monto del abono</label>
                    <div class="ctrl">
                        <span class="pre">₡</span>
                        <input type="text" id="abono-display" inputmode="numeric" autocomplete="off"
                               placeholder="0"
                               value="{{ old('abono') ? number_format((int) old('abono'), 0, '.', '.') : '' }}">
                        <input type="hidden" name="abono" id="abono" value="{{ old('abono', 0) }}">
                    </div>
                    <x-input-error :messages="$errors->get('abono')" class="field-error" />
                </div>
                <p class="form-note">Saldo actual: <strong>{{ colones($prestamo->saldo) }}</strong> · el abono se descuenta directo de este saldo.</p>
            </div>

            {{-- Total del pago (siempre visible, actualizado con JS) --}}
            <div class="panel pago-total-panel" style="margin-bottom: 14px;">
                <h3 style="color: var(--accent-dark);">Total del pago</h3>
                <div class="pago-total-row">
                    <span class="pago-currency">₡</span>
                    <span id="total-display" class="pago-total-num">{{ number_format($minimo, 0, '.', '.') }}</span>
                </div>
                <div class="form-note" style="margin-top: 6px;">
                    @if ($cobrarInteres)
                        Total mínimo + abono al capital
                    @else
                        Solo abono al capital (sin interés por ahora)
                    @endif
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
                <x-input-error :messages="$errors->get('metodo')" class="field-error" style="margin-top: 8px;" />
            </div>

            <button type="submit" class="btn-save">Confirmar pago</button>
        </form>
    </div>

    <script>
        (function () {
            const minimo  = {{ $minimo }};
            const saldo   = {{ $prestamo->saldo }};
            const display = document.getElementById('abono-display');
            const hidden  = document.getElementById('abono');
            const total   = document.getElementById('total-display');

            function parseNum(v) {
                return parseInt(v.replace(/\D/g, ''), 10) || 0;
            }

            function formatNum(n) {
                return n.toLocaleString('es-CR');
            }

            function actualizar() {
                let abono = parseNum(display.value);
                if (abono > saldo) {
                    abono = saldo;
                    display.value = abono > 0 ? formatNum(abono) : '';
                }
                hidden.value = abono;
                total.textContent = formatNum(minimo + abono);
            }

            display.addEventListener('input', function () {
                const raw    = this.value.replace(/\D/g, '');
                const num    = parseInt(raw, 10) || 0;
                const cursor = this.selectionStart;
                const antes  = this.value.length;
                this.value   = num > 0 ? formatNum(num) : '';
                const despues = this.value.length;
                const pos    = Math.max(0, cursor + (despues - antes));
                this.setSelectionRange(pos, pos);
                actualizar();
            });

            document.querySelectorAll('.freq-opt input[type="radio"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    document.querySelectorAll('.freq-opt').forEach(function (opt) {
                        opt.classList.remove('on');
                    });
                    this.closest('.freq-opt').classList.add('on');
                });
            });

            actualizar();
        })();
    </script>
</x-app-layout>
