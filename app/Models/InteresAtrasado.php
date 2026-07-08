<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prestamo_id', 'fecha', 'monto', 'monto_pagado', 'pagado'])]
class InteresAtrasado extends Model
{
    protected $table = 'intereses_atrasados';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha'       => 'date',
            'monto'       => 'integer',
            'monto_pagado' => 'integer',
            'pagado'      => 'boolean',
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
