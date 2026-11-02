<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de administradores
        if (!Schema::hasTable('tb_admins')) {
            Schema::create('tb_admins', function (Blueprint $table) {
                $table->id('id_admin');
                $table->string('username', 100)->unique();
                $table->string('nombre', 150);
                $table->string('password', 255);
                $table->string('rol', 50)->default('superadmin');
                $table->boolean('activo')->default(true);
                $table->timestamp('ultimo_acceso')->nullable();
                $table->timestamps();
            });

            // Insertar administrador principal inicial
            DB::table('tb_admins')->insert([
                'username' => 'admin@Unimap',
                'nombre' => 'Administrador Principal',
                'password' => Hash::make('4rb0lp3rr0'),
                'rol' => 'superadmin',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tabla de sesiones de administradores
        if (!Schema::hasTable('tb_admin_sesiones')) {
            Schema::create('tb_admin_sesiones', function (Blueprint $table) {
                $table->id('id_sesion');
                $table->unsignedBigInteger('id_admin');
                $table->string('username', 100);
                $table->string('ip_acceso', 45)->nullable();
                $table->string('dispositivo', 255)->nullable();
                $table->timestamp('fecha_inicio')->useCurrent();
                $table->timestamp('fecha_cierre')->nullable();
                $table->enum('estado', ['activa', 'cerrada', 'expirada'])->default('activa');
                $table->timestamps();

                $table->foreign('id_admin')
                    ->references('id_admin')
                    ->on('tb_admins')
                    ->onDelete('cascade');
            });
        }

        // 3. Tabla de auditoría y cambios realizados por administradores
        if (!Schema::hasTable('tb_admin_auditoria')) {
            Schema::create('tb_admin_auditoria', function (Blueprint $table) {
                $table->id('id_auditoria');
                $table->unsignedBigInteger('id_admin')->nullable();
                $table->string('admin_username', 100);
                $table->string('tipo_accion', 80); // ej. RESOLUCION_BUG, CAMBIO_ESTADO_REPORTE, INICIO_SESION
                $table->string('modulo', 60);      // ej. Reportes del Mapa, Seguridad, Administradores
                $table->text('descripcion');
                $table->json('detalles_json')->nullable();
                $table->string('ip_origen', 45)->nullable();
                $table->timestamp('fecha_accion')->useCurrent();

                $table->foreign('id_admin')
                    ->references('id_admin')
                    ->on('tb_admins')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_admin_auditoria');
        Schema::dropIfExists('tb_admin_sesiones');
        Schema::dropIfExists('tb_admins');
    }
};
