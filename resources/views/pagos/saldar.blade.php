<x-app-layout>
    <x-slot name="header">
        Saldar cuenta
    </x-slot>

    <a href="{{ route('clientes.show', $cliente) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a {{ $cliente->nombre }} {{ $cliente->apellidos }}
    </a>

    <div class="form-container" style="max-width: 520px;">

        {{-- Desglose de lo que se va a cobrar --}}
        <div class="panel" style="margin-bottom: 14px;">
            <h3>Desglose de la cuenta<span class="sub">Total a cobrar para cerrar la deuda completa</span></h3>

            <div class="kv">
                <span class="k">Deuda principal (saldo)</span>
                <span class="v saldo">{{ colones($prestamo->saldo) }}</span>
            </div>

            @if ($atrasados > 0)
                <div class="kv">
                    <span class="k">Intereses atrasados</span>
                    <span class="v" style="color: #b47d3a;">{{ colones($atrasados) }}</span>
                </div>
            @endif

            @if ($multa > 0)
                <div class="kv">
                    <span class="k">Multa por atraso</span>
                    <span class="v" style="color: var(--red);">{{ colones($multa) }}</span>
                </div>
            @endif

            <div class="kv" style="border-top: 2px solid var(--line); padding-top: 12px; margin-top: 6px;">
                <span class="k" style="font-weight: 700;">TOTAL A COBRAR</span>
                <span class="v" style="font-size: 22px; font-weight: 800; color: var(--accent-dark);">{{ colones($total) }}</span>
            </div>
        </div>

        <div class="panel" style="background: #fff8e6; border-color: #e0c97a;">
            <p style="font-size: 13px; color: #7a6000; margin: 0;">
                <strong>Nota:</strong> el interés del período actual <strong>no está incluido</strong> en este total.
                Si el cliente también quiere pagarlo, registrá primero un pago normal y luego saldá.
            </p>
        </div>

        <form method="POST" action="{{ route('pagos.saldar.store', $cliente) }}" style="margin-top: 14px;">
            @csrf

            <div class="panel">
                <h3>Método de pago</h3>
                <div class="form-field" style="margin: 0;">
                    <div class="ctrl">
                        <select name="metodo" required>
                            <option value="">— Elegí uno —</option>
                            <option value="efectivo"     {{ old('metodo') === 'efectivo'     ? 'selected' : '' }}>Efectivo</option>
                            <option value="sinpe"        {{ old('metodo') === 'sinpe'        ? 'selected' : '' }}>SINPE</option>
                            <option value="transferencia"{{ old('metodo') === 'transferencia'? 'selected' : '' }}>Transferencia</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 14px; background: #fdf3f3; border-color: var(--red);">
                <p style="font-size: 13px; color: #8b1a1a; margin: 0;">
                    Esta acción cierra el préstamo definitivamente.
                    El cliente quedará en estado <strong>Sin préstamo</strong>.
                    No se puede deshacer.
                </p>
            </div>

            <button type="submit" class="btn-primary-full" style="background: var(--red); margin-top: 14px;">
                Confirmar — Saldar {{ colones($total) }}
            </button>
        </form>
    </div>
</x-app-layout>
