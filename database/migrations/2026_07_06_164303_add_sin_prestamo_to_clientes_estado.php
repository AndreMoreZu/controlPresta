<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extender el enum con 'sin-prestamo' sin tocar filas existentes
        DB::statement("ALTER TABLE clientes MODIFY COLUMN estado ENUM('al-dia','atrasado','sin-prestamo') NOT NULL DEFAULT 'al-dia'");
    }

    public function down(): void
    {
        // Limpiar el valor nuevo antes de reducir el enum
        DB::statement("UPDATE clientes SET estado = 'al-dia' WHERE estado = 'sin-prestamo'");
        DB::statement("ALTER TABLE clientes MODIFY COLUMN estado ENUM('al-dia','atrasado') NOT NULL DEFAULT 'al-dia'");
    }
};
