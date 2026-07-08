<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: soporte para pagos parciales (§5.9 del README).
 *
 * Agrega 3 campos nuevos para llevar el saldo pendiente de cada concepto:
 *
 *   prestamos.multa_ya_pagada   — crédito de multa acumulado en el atraso actual.
 *                                 La multa neta = multa_bruta − multa_ya_pagada.
 *                                 Se resetea a 0 cuando el atraso termina (interés
 *                                 del período pagado completo).
 *
 *   prestamos.interes_pendiente — interés del período actual pagado a medias.
 *                                 proximo no avanzó. 0 = sin pendiente.
 *
 *   intereses_atrasados.monto_pagado — cuánto se ha abonado a este registro.
 *                                      pagado → true cuando monto_pagado >= monto.
 *
 * NO se modifican las migraciones existentes.
 * Todos los préstamos/intereses existentes arrancan con 0 en los tres campos,
 * lo cual es correcto: ningún pago parcial estaba registrado antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            // Crédito acumulado de pagos de multa en el ciclo de atraso actual.
            // La multa neta que debe el cliente = multa_bruta − multa_ya_pagada.
            // Se coloca después de multa_acumulada para mantener coherencia visual.
            $table->unsignedInteger('multa_ya_pagada')
                ->default(0)
                ->after('multa_acumulada');

            // Interés del período actual pagado parcialmente. Mientras sea > 0,
            // proximo no avanza y interesPeriodo() devuelve este valor en vez
            // de recalcular. Se coloca después de interes_pagados.
            $table->unsignedInteger('interes_pendiente')
                ->default(0)
                ->after('interes_pagados');
        });

        Schema::table('intereses_atrasados', function (Blueprint $table) {
            // Cuánto se ha pagado de este registro específico de interés atrasado.
            // pagado = true cuando monto_pagado >= monto (interesAtrasado saldado).
            $table->unsignedInteger('monto_pagado')
                ->default(0)
                ->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn(['multa_ya_pagada', 'interes_pendiente']);
        });

        Schema::table('intereses_atrasados', function (Blueprint $table) {
            $table->dropColumn('monto_pagado');
        });
    }
};
