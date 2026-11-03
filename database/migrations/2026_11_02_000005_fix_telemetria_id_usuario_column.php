<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tb_telemetria_cargas_unity')) {
            try {
                // Compatible con PostgreSQL y MySQL
                $driver = DB::getDriverName();
                if ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE tb_telemetria_cargas_unity ALTER COLUMN id_usuario TYPE VARCHAR(100) USING id_usuario::text');
                } elseif ($driver === 'mysql') {
                    DB::statement('ALTER TABLE tb_telemetria_cargas_unity MODIFY id_usuario VARCHAR(100) NULL');
                } elseif ($driver === 'sqlite') {
                    // SQLite maneja tipos dinámicamente
                }
            } catch (\Exception $e) {
                // Silencioso si la columna ya es varchar
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
