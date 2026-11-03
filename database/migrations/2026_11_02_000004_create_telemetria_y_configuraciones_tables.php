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
        // 1. Tabla de Configuración de Visibilidad de Módulos (para usuarios finales)
        if (!Schema::hasTable('tb_configuracion_modulos')) {
            Schema::create('tb_configuracion_modulos', function (Blueprint $table) {
                $table->id('id_config');
                $table->string('clave', 80)->unique();
                $table->boolean('mostrar_mapa_3d')->default(true);
                $table->boolean('mostrar_mapa_gps')->default(true);
                $table->boolean('mostrar_recorridos')->default(true);
                $table->timestamps();
            });

            // Registro inicial por defecto
            DB::table('tb_configuracion_modulos')->insert([
                'clave' => 'modulos_usuario',
                'mostrar_mapa_3d' => true,
                'mostrar_mapa_gps' => true,
                'mostrar_recorridos' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tabla de Telemetría de Carga de Unity WebGL y Red
        if (!Schema::hasTable('tb_telemetria_cargas_unity')) {
            Schema::create('tb_telemetria_cargas_unity', function (Blueprint $table) {
                $table->id('id_telemetria');
                $table->unsignedBigInteger('id_usuario')->nullable();
                $table->string('nombre_usuario', 150)->nullable();
                $table->decimal('tiempo_carga_segundos', 6, 2);
                $table->string('tipo_conexion', 100)->nullable(); // WiFi, Datos Móviles (4G), etc.
                $table->string('operador', 100)->nullable();      // Tigo, Claro, Movistar, etc.
                $table->string('modelo_dispositivo', 200)->nullable(); // Samsung Galaxy A55 5G, etc.
                $table->string('ip_origen', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index('id_usuario');
                $table->index('tipo_conexion');
                $table->index('operador');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_telemetria_cargas_unity');
        Schema::dropIfExists('tb_configuracion_modulos');
    }
};
