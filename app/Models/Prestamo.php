<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cliente_id',
    'monto',
    'saldo',
    'frecuencia',
    'interes_pagados',
    'multa_acumulada',
    'dias_atraso',
    'atraso_desde',
    'inicio',
    'proximo',
    'vencido',
    'estado',
])]
class Prestamo extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'integer',
            'saldo' => 'integer',
            'interes_pagados' => 'integer',
            'multa_acumulada' => 'integer',
            'dias_atraso' => 'integer',
            'atraso_desde' => 'date',
            'inicio' => 'date',
            'proximo' => 'date',
            'vencido' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function interesesAtrasados(): HasMany
    {
        return $this->hasMany(InteresAtrasado::class);
    }
}
