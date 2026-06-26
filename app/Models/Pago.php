<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'prestamo_id',
    'fecha',
    'monto_total',
    'interes',
    'abono',
    'interes_atrasado_pagado',
    'multa_pagada',
    'metodo',
    'recibido_por',
    'es_saldo',
])]
class Pago extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_total' => 'integer',
            'interes' => 'integer',
            'abono' => 'integer',
            'interes_atrasado_pagado' => 'integer',
            'multa_pagada' => 'integer',
            'es_saldo' => 'boolean',
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }
}
