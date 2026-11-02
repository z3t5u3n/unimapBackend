<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea todas las tablas base de UniMap con soporte nativo para PostgreSQL y MySQL.
     */
    public function up(): void
    {
        // 1. Catálogo de Facultades
        if (!Schema::hasTable('tb_facultades')) {
            Schema::create('tb_facultades', function (Blueprint $table) {
                $table->increments('id_facultades');
                $table->string('facultad', 255);
            });
        }

        // 2. Catálogo de Carreras / Programas Académicos
        if (!Schema::hasTable('tb_carreras')) {
            Schema::create('tb_carreras', function (Blueprint $table) {
                $table->increments('id_carrera');
                $table->string('carrera', 255);
            });
        }

        // 3. Catálogo de Semestres
        if (!Schema::hasTable('tb_semestre')) {
            Schema::create('tb_semestre', function (Blueprint $table) {
                $table->increments('id_semestres');
                $table->string('semestre', 50);
            });
        }

        // 4. Catálogo de Tipos de Documento
        if (!Schema::hasTable('tb_documentos')) {
            Schema::create('tb_documentos', function (Blueprint $table) {
                $table->increments('id_documento');
                $table->string('tipo_documento', 100);
            });
        }

        // 5. Catálogo de Tipos de Usuario (General, Estudiante, Docente)
        if (!Schema::hasTable('tb_tipo_usuario')) {
            Schema::create('tb_tipo_usuario', function (Blueprint $table) {
                $table->increments('id_tipo');
                $table->string('tipo_usuario', 50);
            });
        }

        // 6. Usuarios Generales de UniMap
        if (!Schema::hasTable('tb_usuario_general')) {
            Schema::create('tb_usuario_general', function (Blueprint $table) {
                $table->increments('id_general');
                $table->string('id_usuario', 30)->unique();
                $table->string('nombres', 100);
                $table->string('apellidos', 100);
                $table->unsignedInteger('tipo_documento');
                $table->string('numero_documento', 50)->unique();
                $table->string('correo_institucional', 150)->nullable();
                $table->unsignedInteger('tipo_usuario')->default(1);
                $table->timestamp('fecha_sys')->useCurrent();

                $table->foreign('tipo_documento')
                    ->references('id_documento')
                    ->on('tb_documentos')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');

                $table->foreign('tipo_usuario')
                    ->references('id_tipo')
                    ->on('tb_tipo_usuario')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        // 7. Datos Académicos Extendidos para Estudiantes
        if (!Schema::hasTable('tb_estudiante')) {
            Schema::create('tb_estudiante', function (Blueprint $table) {
                $table->increments('id_estudiante');
                $table->string('id_usuario', 30)->unique();
                $table->string('correo_institucional', 150)->unique();
                $table->unsignedInteger('id_carrera');
                $table->unsignedInteger('id_semestre');
                $table->timestamp('fecha_registro')->useCurrent();

                $table->foreign('id_usuario')
                    ->references('id_usuario')
                    ->on('tb_usuario_general')
                    ->onDelete('cascade');

                $table->foreign('id_carrera')
                    ->references('id_carrera')
                    ->on('tb_carreras')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');

                $table->foreign('id_semestre')
                    ->references('id_semestres')
                    ->on('tb_semestre')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        // 8. Historial de Inicios de Sesión
        if (!Schema::hasTable('tb_inicio_sesion')) {
            Schema::create('tb_inicio_sesion', function (Blueprint $table) {
                $table->increments('id_sesion');
                $table->string('id_usuario', 30);
                $table->string('ip_acceso', 45)->nullable();
                $table->string('dispositivo', 255)->nullable();
                $table->timestamp('fecha_ingreso')->useCurrent();

                $table->foreign('id_usuario')
                    ->references('id_usuario')
                    ->on('tb_usuario_general')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        // 9. Lugares Recorridos en el Mapa 3D
        if (!Schema::hasTable('tb_lugares_recorridos')) {
            Schema::create('tb_lugares_recorridos', function (Blueprint $table) {
                $table->increments('id_recorrido');
                $table->string('id_usuario', 30);
                $table->string('lugar_mesh', 150);
                $table->string('origen_busqueda', 50)->default('buscador');
                $table->timestamp('fecha_busqueda')->useCurrent();

                $table->foreign('id_usuario')
                    ->references('id_usuario')
                    ->on('tb_usuario_general')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        // 10. Reportes de Incidencias / Errores del Mapa
        if (!Schema::hasTable('tb_reportes_mapa')) {
            Schema::create('tb_reportes_mapa', function (Blueprint $table) {
                $table->id('id_reporte');
                $table->string('id_usuario', 30);
                $table->string('tipo_error', 100);
                $table->string('lugar_afectado', 150)->nullable();
                $table->text('descripcion');
                $table->string('estado', 30)->default('pendiente');
                $table->timestamp('fecha_reporte')->useCurrent();

                $table->foreign('id_usuario')
                    ->references('id_usuario')
                    ->on('tb_usuario_general')
                    ->onDelete('cascade');
            });
        }

        // 11. Tokens de Verificación para Cambio de Rol a Estudiante (5 minutos)
        if (!Schema::hasTable('tb_verificaciones_rol')) {
            Schema::create('tb_verificaciones_rol', function (Blueprint $table) {
                $table->increments('id_verificacion');
                $table->string('id_usuario', 30);
                $table->string('correo_institucional', 150);
                $table->unsignedInteger('nuevo_tipo_usuario')->default(2);
                $table->unsignedInteger('id_carrera')->nullable();
                $table->unsignedInteger('id_semestre')->nullable();
                $table->string('token', 100)->unique();
                $table->dateTime('expira_en');
                $table->boolean('utilizado')->default(false);
                $table->timestamp('fecha_creacion')->useCurrent();

                $table->foreign('id_usuario')
                    ->references('id_usuario')
                    ->on('tb_usuario_general')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_verificaciones_rol');
        Schema::dropIfExists('tb_reportes_mapa');
        Schema::dropIfExists('tb_lugares_recorridos');
        Schema::dropIfExists('tb_inicio_sesion');
        Schema::dropIfExists('tb_estudiante');
        Schema::dropIfExists('tb_usuario_general');
        Schema::dropIfExists('tb_tipo_usuario');
        Schema::dropIfExists('tb_documentos');
        Schema::dropIfExists('tb_semestre');
        Schema::dropIfExists('tb_carreras');
        Schema::dropIfExists('tb_facultades');
    }
};
