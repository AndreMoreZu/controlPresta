<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Red de seguridad del motor de atraso:
 * garantiza a nivel de DB que nunca existan dos registros de interés atrasado
 * para el mismo préstamo y la misma fecha, aunque el código falle en el chequeo.
 *
 * El motor ya verifica antes de insertar (array $fechasExistentes), pero un
 * unique constraint en DB es la última línea de defensa contra registros duplicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intereses_atrasados', function (Blueprint $table) {
            $table->unique(['prestamo_id', 'fecha'], 'intereses_atrasados_prestamo_fecha_unique');
        });
    }

    public function down(): void
    {
        Schema::table('intereses_atrasados', function (Blueprint $table) {
            $table->dropUnique('intereses_atrasados_prestamo_fecha_unique');
        });
    }
};
