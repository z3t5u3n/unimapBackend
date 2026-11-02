<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Población de catálogos y datos esenciales para PostgreSQL y MySQL.
     * Idempotente (utiliza updateOrInsert para evitar duplicados en re-ejecuciones).
     */
    public function run(): void
    {
        // 1. Tipos de Documento
        $documentos = [
            ['id_documento' => 1, 'tipo_documento' => 'Cedula de Ciudadania'],
            ['id_documento' => 2, 'tipo_documento' => 'Tarjeta de identidad'],
        ];
        foreach ($documentos as $doc) {
            DB::table('tb_documentos')->updateOrInsert(
                ['id_documento' => $doc['id_documento']],
                ['tipo_documento' => $doc['tipo_documento']]
            );
        }

        // 2. Tipos de Usuario
        $tipos = [
            ['id_tipo' => 1, 'tipo_usuario' => 'general'],
            ['id_tipo' => 2, 'tipo_usuario' => 'estudiante'],
            ['id_tipo' => 3, 'tipo_usuario' => 'profesor'],
        ];
        foreach ($tipos as $tipo) {
            DB::table('tb_tipo_usuario')->updateOrInsert(
                ['id_tipo' => $tipo['id_tipo']],
                ['tipo_usuario' => $tipo['tipo_usuario']]
            );
        }

        // 3. Facultades
        $facultades = [
            ['id_facultades' => 1, 'facultad' => 'Facultad de Economía, Administración y Contaduría Pública'],
            ['id_facultades' => 2, 'facultad' => 'Facultad de Ingeniería y Ciencias Agroindustriales'],
            ['id_facultades' => 3, 'facultad' => 'Facultad de Ciencias Sociales, Salud y Educación'],
        ];
        foreach ($facultades as $fac) {
            DB::table('tb_facultades')->updateOrInsert(
                ['id_facultades' => $fac['id_facultades']],
                ['facultad' => $fac['facultad']]
            );
        }

        // 4. Semestres (1 a 10)
        for ($i = 1; $i <= 10; $i++) {
            DB::table('tb_semestre')->updateOrInsert(
                ['id_semestres' => $i],
                ['semestre' => "Semestre {$i}"]
            );
        }

        // 5. Carreras / Programas Académicos Oficiales ITFIP
        $carreras = [
            ['id_carrera' => 1, 'carrera' => 'Administración de Empresas'],
            ['id_carrera' => 2, 'carrera' => 'Contaduría Pública'],
            ['id_carrera' => 3, 'carrera' => 'Economía'],
            ['id_carrera' => 4, 'carrera' => 'Gestión Empresarial'],
            ['id_carrera' => 5, 'carrera' => 'Gestión Contable y Financiera'],
            ['id_carrera' => 6, 'carrera' => 'Gestión Logística'],
            ['id_carrera' => 7, 'carrera' => 'Procesos Administrativos'],
            ['id_carrera' => 8, 'carrera' => 'Operaciones Contables'],
            ['id_carrera' => 9, 'carrera' => 'Procesos Logísticos Empresariales'],
            ['id_carrera' => 10, 'carrera' => 'Ingeniería de Sistemas'],
            ['id_carrera' => 11, 'carrera' => 'Ingeniería Civil'],
            ['id_carrera' => 12, 'carrera' => 'Ingeniería Electrónica'],
            ['id_carrera' => 13, 'carrera' => 'Ingeniería Agronómica'],
            ['id_carrera' => 14, 'carrera' => 'Ingeniería Ambiental'],
            ['id_carrera' => 15, 'carrera' => 'Gestión Informática'],
            ['id_carrera' => 16, 'carrera' => 'Gestión de la Construcción'],
            ['id_carrera' => 17, 'carrera' => 'Automatización Electrónica Industrial por Ciclos Propedéuticos'],
            ['id_carrera' => 18, 'carrera' => 'Producción de Agrocultivos'],
            ['id_carrera' => 19, 'carrera' => 'Tecnología en Gestión Ambiental'],
            ['id_carrera' => 20, 'carrera' => 'Técnica Profesional en Programación Web / Soluciones Web'],
            ['id_carrera' => 21, 'carrera' => 'Construcción de Edificaciones'],
            ['id_carrera' => 22, 'carrera' => 'Mantenimiento Electrónico'],
            ['id_carrera' => 23, 'carrera' => 'Monitoreo Agrícola'],
            ['id_carrera' => 24, 'carrera' => 'Técnica Profesional en Monitoreo Ambiental'],
            ['id_carrera' => 25, 'carrera' => 'Trabajo Social'],
            ['id_carrera' => 26, 'carrera' => 'Actividad Física y Deporte'],
            ['id_carrera' => 27, 'carrera' => 'Gestión Comunitaria / Gestión Social'],
            ['id_carrera' => 28, 'carrera' => 'Gestión Deportiva'],
            ['id_carrera' => 29, 'carrera' => 'Operación de Servicios Comunitarios / Promoción Social'],
            ['id_carrera' => 30, 'carrera' => 'Entrenamiento Deportivo'],
        ];
        foreach ($carreras as $c) {
            DB::table('tb_carreras')->updateOrInsert(
                ['id_carrera' => $c['id_carrera']],
                ['carrera' => $c['carrera']]
            );
        }

        // 6. Administradores Iniciales
        DB::table('tb_admins')->updateOrInsert(
            ['username' => 'admin@Unimap'],
            [
                'nombre' => 'Administrador Principal',
                'password' => Hash::make('4rb0lp3rr0'),
                'rol' => 'superadmin',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('tb_admins')->updateOrInsert(
            ['username' => 'soporte@Unimap'],
            [
                'nombre' => 'Soporte Técnico',
                'password' => Hash::make('4rb0lp3rr0'),
                'rol' => 'administrador',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 7. Notificación Inicial de Bienvenida
        if (DB::table('tb_notificaciones')->count() === 0) {
            DB::table('tb_notificaciones')->insert([
                'titulo' => '¡Bienvenidos al Campus 3D UniMap ITFIP!',
                'mensaje' => 'Explora de forma inmersiva todos los bloques, salones y zonas del campus universitario en tiempo real.',
                'tipo' => 'aviso',
                'activo' => true,
                'creado_por' => 'admin@Unimap',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Encuesta Inicial Activa
        if (DB::table('tb_encuestas')->count() === 0) {
            DB::table('tb_encuestas')->insert([
                'titulo' => 'Satisfacción y Usabilidad UniMap 3D ITFIP',
                'descripcion' => 'Califica tu experiencia de navegación, facilidad de búsqueda y rendimiento gráfico del campus 3D.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
