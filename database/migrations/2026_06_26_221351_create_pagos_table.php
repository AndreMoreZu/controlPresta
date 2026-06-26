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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('monto_total');
            $table->unsignedInteger('interes')->default(0);
            $table->unsignedInteger('abono')->default(0);
            $table->unsignedInteger('interes_atrasado_pagado')->default(0);
            $table->unsignedInteger('multa_pagada')->default(0);
            $table->enum('metodo', ['efectivo', 'sinpe', 'transferencia']);
            $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('es_saldo')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
