<x-app-layout>
    <x-slot name="header">
        Panel principal
    </x-slot>

    <div class="cards three">
        <div class="stat">
            <div class="lab">Total en la calle</div>
            <div class="val">—</div>
            <div class="sub">Próximamente</div>
        </div>
        <div class="stat">
            <div class="lab">Clientes al día</div>
            <div class="val">—</div>
            <div class="sub">Próximamente</div>
        </div>
        <div class="stat warn">
            <div class="lab">Clientes atrasados</div>
            <div class="val">—</div>
            <div class="sub">Próximamente</div>
        </div>
    </div>

    <div class="section-title">Pagos de esta semana</div>
    <div class="panel">
        <div class="text-muted" style="color: var(--muted); font-size: 13.5px;">Todavía no hay datos de clientes ni préstamos.</div>
    </div>
</x-app-layout>
