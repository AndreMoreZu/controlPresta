<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cedula_foto_frente')->nullable()->after('cedula_foto');
            $table->string('cedula_foto_atras')->nullable()->after('cedula_foto_frente');
        });

        DB::statement('UPDATE clientes SET cedula_foto_frente = cedula_foto WHERE cedula_foto IS NOT NULL');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cedula_foto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cedula_foto')->nullable()->after('cedula');
        });

        DB::statement('UPDATE clientes SET cedula_foto = cedula_foto_frente WHERE cedula_foto_frente IS NOT NULL');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['cedula_foto_frente', 'cedula_foto_atras']);
        });
    }
};
