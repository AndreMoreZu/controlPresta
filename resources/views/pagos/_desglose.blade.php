{{-- Desglose de conceptos de un pago. Requiere: $pago --}}
@if ($pago->interes > 0)
    <span class="gh-int">Interés {{ colones($pago->interes) }}</span>
@endif
@if ($pago->interes_atrasado_pagado > 0)
    <span class="gh-atr">Interés atrasado {{ colones($pago->interes_atrasado_pagado) }}</span>
@endif
@if ($pago->multa_pagada > 0)
    <span class="gh-multa">Multa {{ colones($pago->multa_pagada) }}</span>
@endif
@if ($pago->abono > 0)
    <span>Abono {{ colones($pago->abono) }}</span>
@endif
<span class="gh-metodo">{{ ucfirst($pago->metodo) }}</span>
