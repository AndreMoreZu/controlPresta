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
        Schema::create('intereses_atrasados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('monto');
            $table->boolean('pagado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intereses_atrasados');
    }
};
