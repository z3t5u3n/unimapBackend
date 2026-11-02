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
        // 1. Tabla de Notificaciones / Comunicados del Administrador
        if (!Schema::hasTable('tb_notificaciones')) {
            Schema::create('tb_notificaciones', function (Blueprint $table) {
                $table->id('id_notificacion');
                $table->string('titulo', 150);
                $table->text('mensaje');
                $table->enum('tipo', ['mantenimiento', 'actualizacion', 'aviso', 'urgente'])->default('aviso');
                $table->boolean('activo')->default(true);
                $table->string('creado_por', 100)->default('admin@Unimap');
                $table->timestamps();
            });

            // Notificación inicial de bienvenida / estado
            DB::table('tb_notificaciones')->insert([
                'titulo' => 'Actualización del Campus Virtual 3D',
                'mensaje' => 'Bienvenidos a la nueva versión de UniMap. Se han optimizado las rutas de navegación y el rendimiento gráfico en todos los bloques.',
                'tipo' => 'actualizacion',
                'activo' => true,
                'creado_por' => 'admin@Unimap',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tabla de Encuestas de Satisfacción
        if (!Schema::hasTable('tb_encuestas')) {
            Schema::create('tb_encuestas', function (Blueprint $table) {
                $table->id('id_encuesta');
                $table->string('titulo', 200);
                $table->text('descripcion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });

            // Encuesta principal de satisfacción
            DB::table('tb_encuestas')->insert([
                'titulo' => 'Encuesta de Satisfacción y Experiencia UniMap 3D',
                'descripcion' => 'Tu opinión nos ayuda a optimizar los tiempos de carga, precisión del mapa y usabilidad del campus.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Tabla de Respuestas de Usuarios a las Encuestas
        if (!Schema::hasTable('tb_respuestas_encuesta')) {
            Schema::create('tb_respuestas_encuesta', function (Blueprint $table) {
                $table->id('id_respuesta');
                $table->unsignedBigInteger('id_encuesta');
                $table->string('id_usuario', 50);
                $table->unsignedTinyInteger('calificacion_general')->default(5); // 1 a 5 estrellas
                $table->unsignedTinyInteger('facilidad_uso')->default(5);       // 1 a 5
                $table->unsignedTinyInteger('calidad_mapa_3d')->default(5);       // 1 a 5
                $table->text('comentarios')->nullable();
                $table->timestamps();

                $table->foreign('id_encuesta')
                    ->references('id_encuesta')
                    ->on('tb_encuestas')
                    ->onDelete('cascade');
            });

            // Semilla de respuestas iniciales para estadísticas gráficas
            DB::table('tb_respuestas_encuesta')->insert([
                [
                    'id_encuesta' => 1,
                    'id_usuario' => 'EST-DEMO-01',
                    'calificacion_general' => 5,
                    'facilidad_uso' => 5,
                    'calidad_mapa_3d' => 5,
                    'comentarios' => 'Excelente experiencia 3D para ubicar los salones del bloque E.',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
                [
                    'id_encuesta' => 1,
                    'id_usuario' => 'EST-DEMO-02',
                    'calificacion_general' => 4,
                    'facilidad_uso' => 4,
                    'calidad_mapa_3d' => 5,
                    'comentarios' => 'Muy útil el buscador y la vista aérea del campus.',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
                [
                    'id_encuesta' => 1,
                    'id_usuario' => 'VISIT-DEMO-01',
                    'calificacion_general' => 5,
                    'facilidad_uso' => 4,
                    'calidad_mapa_3d' => 5,
                    'comentarios' => 'Me facilitó llegar a la cafetería y auditorio.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_respuestas_encuesta');
        Schema::dropIfExists('tb_encuestas');
        Schema::dropIfExists('tb_notificaciones');
    }
};
