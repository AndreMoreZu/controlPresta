<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('monto');
            $table->unsignedInteger('saldo');
            $table->enum('frecuencia', ['mensual', 'quincenal', 'semanal']);
            $table->unsignedInteger('interes_pagados')->default(0);
            $table->unsignedInteger('multa_acumulada')->default(0);
            $table->unsignedInteger('dias_atraso')->default(0);
            $table->date('inicio')->nullable();
            $table->date('proximo')->nullable();
            $table->boolean('vencido')->default(false);
            $table->enum('estado', ['activo', 'saldado'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
