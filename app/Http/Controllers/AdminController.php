<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\UsuarioGeneral;
use App\Models\InicioSesion;
use App\Models\LugarRecorrido;
use App\Models\TipoUsuario;
use App\Models\ReporteMapa;
use App\Models\Admin;
use App\Models\AdminSesion;
use App\Models\AdminAuditoria;
use App\Models\Notificacion;
use App\Models\Encuesta;
use App\Models\RespuestaEncuesta;
use App\Models\Tester;

class AdminController extends Controller
{
    /**
     * Iniciar sesión de administrador
     */
    public function loginAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Por favor ingrese su usuario o correo administrativo.',
            'password.required' => 'Por favor ingrese su contraseña.',
        ]);

        $admin = Admin::where('username', $validated['username'])->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'message' => 'Credenciales administrativas inválidas. Verifique su usuario y contraseña.',
            ], 401);
        }

        if (!$admin->activo) {
            return response()->json([
                'message' => 'Esta cuenta de administrador se encuentra inactiva.',
            ], 403);
        }

        // Actualizar último acceso
        $admin->ultimo_acceso = now();
        $admin->save();

        // 1. Registrar sesión en tb_admin_sesiones
        $sesion = AdminSesion::create([
            'id_admin' => $admin->id_admin,
            'username' => $admin->username,
            'ip_acceso' => $request->ip(),
            'dispositivo' => substr($request->userAgent() ?? 'Navegador Web', 0, 255),
            'fecha_inicio' => now(),
            'estado' => 'activa',
        ]);

        // 2. Registrar auditoría de inicio de sesión en tb_admin_auditoria
        AdminAuditoria::create([
            'id_admin' => $admin->id_admin,
            'admin_username' => $admin->username,
            'tipo_accion' => 'INICIO_SESION',
            'modulo' => 'Seguridad',
            'descripcion' => "Inicio de sesión exitoso del administrador '{$admin->username}' ({$admin->nombre}).",
            'detalles_json' => [
                'ip' => $request->ip(),
                'dispositivo' => $request->userAgent(),
                'id_sesion' => $sesion->id_sesion,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Bienvenido al panel administrativo de UniMap, ' . $admin->nombre,
            'admin' => [
                'id_admin' => $admin->id_admin,
                'username' => $admin->username,
                'nombre' => $admin->nombre,
                'rol' => $admin->rol,
            ],
            'id_sesion' => $sesion->id_sesion,
        ], 200);
    }

    /**
     * Cerrar sesión de administrador
     */
    public function logoutAdmin(Request $request): JsonResponse
    {
        $idSesion = $request->input('id_sesion');
        $username = $request->input('admin_username', 'admin@Unimap');

        if ($idSesion) {
            $sesion = AdminSesion::find($idSesion);
            if ($sesion && $sesion->estado === 'activa') {
                $sesion->fecha_cierre = now();
                $sesion->estado = 'cerrada';
                $sesion->save();
            }
        }

        // Registrar auditoría de cierre de sesión
        $admin = Admin::where('username', $username)->first();
        AdminAuditoria::create([
            'id_admin' => $admin ? $admin->id_admin : null,
            'admin_username' => $username,
            'tipo_accion' => 'CIERRE_SESION',
            'modulo' => 'Seguridad',
            'descripcion' => "Cierre de sesión voluntario del administrador '{$username}'.",
            'detalles_json' => [
                'id_sesion' => $idSesion,
                'ip' => $request->ip(),
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Sesión administrativa cerrada exitosamente.',
        ], 200);
    }

    /**
     * Registrar nuevo administrador
     */
    public function crearAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:tb_admins,username',
            'nombre' => 'required|string|max:150',
            'password' => 'required|string|min:6',
            'rol' => 'nullable|string|max:50',
            'creador_username' => 'nullable|string',
        ], [
            'username.unique' => 'Este nombre de usuario / correo ya se encuentra registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $creadorUsername = $validated['creador_username'] ?? 'admin@Unimap';
        $adminCreador = Admin::where('username', $creadorUsername)->first();

        $nuevoAdmin = Admin::create([
            'username' => $validated['username'],
            'nombre' => $validated['nombre'],
            'password' => Hash::make($validated['password']),
            'rol' => $validated['rol'] ?? 'administrador',
            'activo' => true,
        ]);

        // Registrar auditoría
        AdminAuditoria::create([
            'id_admin' => $adminCreador ? $adminCreador->id_admin : null,
            'admin_username' => $creadorUsername,
            'tipo_accion' => 'REGISTRO_ADMIN',
            'modulo' => 'Gestión Administradores',
            'descripcion' => "Se creó la nueva cuenta de administrador '{$nuevoAdmin->username}' ({$nuevoAdmin->nombre}) con rol '{$nuevoAdmin->rol}'.",
            'detalles_json' => [
                'nuevo_id_admin' => $nuevoAdmin->id_admin,
                'nuevo_username' => $nuevoAdmin->username,
                'rol' => $nuevoAdmin->rol,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Administrador registrado exitosamente.',
            'admin' => [
                'id_admin' => $nuevoAdmin->id_admin,
                'username' => $nuevoAdmin->username,
                'nombre' => $nuevoAdmin->nombre,
                'rol' => $nuevoAdmin->rol,
            ],
        ], 201);
    }

    /**
     * Datos completos para el Dashboard administrativo
     */
    public function dashboard(): JsonResponse
    {
        // 1. Usuarios con sus tipos, documentos asociados y datos de estudiante
        $usuarios = UsuarioGeneral::leftJoin('tb_documentos', 'tb_usuario_general.tipo_documento', '=', 'tb_documentos.id_documento')
            ->leftJoin('tb_tipo_usuario', 'tb_usuario_general.tipo_usuario', '=', 'tb_tipo_usuario.id_tipo')
            ->leftJoin('tb_estudiante', 'tb_usuario_general.id_usuario', '=', 'tb_estudiante.id_usuario')
            ->leftJoin('tb_carreras', 'tb_estudiante.id_carrera', '=', 'tb_carreras.id_carrera')
            ->leftJoin('tb_semestre', 'tb_estudiante.id_semestre', '=', 'tb_semestre.id_semestres')
            ->select(
                'tb_usuario_general.id_general',
                'tb_usuario_general.id_usuario',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos',
                'tb_usuario_general.numero_documento',
                'tb_usuario_general.correo_institucional',
                'tb_carreras.carrera as nombre_carrera',
                'tb_semestre.semestre as nombre_semestre',
                'tb_documentos.tipo_documento as nombre_documento',
                'tb_tipo_usuario.tipo_usuario as nombre_tipo',
                'tb_usuario_general.fecha_sys'
            )
            ->orderBy('tb_usuario_general.id_general', 'desc')
            ->get();

        // 2. Historial de inicios de sesión de estudiantes y visitantes
        $sesiones = InicioSesion::leftJoin('tb_usuario_general', 'tb_inicio_sesion.id_usuario', '=', 'tb_usuario_general.id_usuario')
            ->select(
                'tb_inicio_sesion.id_sesion',
                'tb_inicio_sesion.id_usuario',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos',
                'tb_inicio_sesion.ip_acceso',
                'tb_inicio_sesion.dispositivo',
                'tb_inicio_sesion.fecha_ingreso'
            )
            ->orderBy('tb_inicio_sesion.fecha_ingreso', 'desc')
            ->limit(150)
            ->get();

        // 3. Historial de lugares recorridos / buscados
        $recorridos = LugarRecorrido::leftJoin('tb_usuario_general', 'tb_lugares_recorridos.id_usuario', '=', 'tb_usuario_general.id_usuario')
            ->select(
                'tb_lugares_recorridos.id_recorrido',
                'tb_lugares_recorridos.id_usuario',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos',
                'tb_lugares_recorridos.lugar_mesh',
                'tb_lugares_recorridos.origen_busqueda',
                'tb_lugares_recorridos.fecha_busqueda'
            )
            ->orderBy('tb_lugares_recorridos.fecha_busqueda', 'desc')
            ->limit(150)
            ->get();

        // 4. Reportes de errores / bugs del mapa
        $reportes = ReporteMapa::leftJoin('tb_usuario_general', 'tb_reportes_mapa.id_usuario', '=', 'tb_usuario_general.id_usuario')
            ->select(
                'tb_reportes_mapa.id_reporte',
                'tb_reportes_mapa.id_usuario',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos',
                'tb_reportes_mapa.tipo_error',
                'tb_reportes_mapa.lugar_afectado',
                'tb_reportes_mapa.descripcion',
                'tb_reportes_mapa.estado',
                'tb_reportes_mapa.fecha_reporte'
            )
            ->orderBy('tb_reportes_mapa.fecha_reporte', 'desc')
            ->get();

        // 5. Administradores registrados
        $admins = Admin::select(
                'id_admin',
                'username',
                'nombre',
                'rol',
                'activo',
                'ultimo_acceso',
                'created_at'
            )
            ->orderBy('id_admin', 'asc')
            ->get();

        // 6. Sesiones de administradores
        $adminSesiones = AdminSesion::orderBy('fecha_inicio', 'desc')
            ->limit(100)
            ->get();

        // 7. Auditoría y cambios realizados por administradores
        $adminAuditorias = AdminAuditoria::orderBy('fecha_accion', 'desc')
            ->limit(150)
            ->get();

        // 8. Tipos de usuario registrados
        $tipos = TipoUsuario::all();

        // ==============================================================
        // CÁLCULO DE ESTADÍSTICAS INTELIGENTES
        // ==============================================================

        $driver = DB::connection()->getDriverName();

        // A. Mes con más registros
        $mesesSql = $driver === 'pgsql'
            ? "TO_CHAR(fecha_sys, 'YYYY-MM') as mes_clave, COUNT(*) as cantidad"
            : "DATE_FORMAT(fecha_sys, '%Y-%m') as mes_clave, COUNT(*) as cantidad";
        $mesesGroup = $driver === 'pgsql' ? DB::raw("TO_CHAR(fecha_sys, 'YYYY-MM')") : "mes_clave";

        $mesesRaw = DB::table('tb_usuario_general')
            ->selectRaw($mesesSql)
            ->whereNotNull('fecha_sys')
            ->groupBy($mesesGroup)
            ->orderBy('mes_clave', 'asc')
            ->get();

        $mesesNombres = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        $estadisticasMeses = [];
        $mesTop = null;
        $maxMesCount = -1;

        foreach ($mesesRaw as $m) {
            $parts = explode('-', $m->mes_clave);
            $nombreMes = isset($parts[1]) && isset($mesesNombres[$parts[1]]) 
                ? $mesesNombres[$parts[1]] . ' ' . $parts[0] 
                : $m->mes_clave;

            $item = [
                'clave' => $m->mes_clave,
                'nombre' => $nombreMes,
                'cantidad' => (int)$m->cantidad,
            ];
            $estadisticasMeses[] = $item;

            if ((int)$m->cantidad > $maxMesCount) {
                $maxMesCount = (int)$m->cantidad;
                $mesTop = $item;
            }
        }

        // B. Día de la semana con más inicios de sesión
        $diasSql = $driver === 'pgsql'
            ? "(EXTRACT(ISODOW FROM fecha_ingreso) - 1)::int as dia_num, COUNT(*) as cantidad"
            : "WEEKDAY(fecha_ingreso) as dia_num, COUNT(*) as cantidad";
        $diasGroup = $driver === 'pgsql' ? DB::raw("(EXTRACT(ISODOW FROM fecha_ingreso) - 1)::int") : "dia_num";

        $diasRaw = DB::table('tb_inicio_sesion')
            ->selectRaw($diasSql)
            ->whereNotNull('fecha_ingreso')
            ->groupBy($diasGroup)
            ->orderBy('dia_num', 'asc')
            ->get();

        $nombresDias = [
            0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles',
            3 => 'Jueves', 4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo',
        ];

        $estadisticasDias = [];
        $diaTop = null;
        $maxDiaCount = -1;

        for ($i = 0; $i < 7; $i++) {
            $estadisticasDias[$i] = [
                'dia_num' => $i,
                'nombre' => $nombresDias[$i],
                'cantidad' => 0,
            ];
        }

        foreach ($diasRaw as $d) {
            $num = (int)$d->dia_num;
            if (isset($estadisticasDias[$num])) {
                $cant = (int)$d->cantidad;
                $estadisticasDias[$num]['cantidad'] = $cant;
                if ($cant > $maxDiaCount) {
                    $maxDiaCount = $cant;
                    $diaTop = $estadisticasDias[$num];
                }
            }
        }
        $estadisticasDias = array_values($estadisticasDias);

        // C. Bloques más buscados (usando subconsulta estándar compatible con PostgreSQL y MySQL)
        $bloquesRaw = DB::table(function ($query) {
            $query->from('tb_lugares_recorridos')
                ->selectRaw("
                    CASE 
                        WHEN lugar_mesh LIKE '%-E%' THEN 'Bloque E'
                        WHEN lugar_mesh LIKE '%-D%' THEN 'Bloque D'
                        WHEN lugar_mesh LIKE '%-A%' THEN 'Bloque A'
                        WHEN lugar_mesh LIKE '%-B%' THEN 'Bloque B'
                        WHEN lugar_mesh LIKE '%-C%' THEN 'Bloque C'
                        WHEN lugar_mesh LIKE '%Cafeteria%' THEN 'Cafetería'
                        WHEN lugar_mesh LIKE '%Coliseo%' THEN 'Coliseo'
                        WHEN lugar_mesh LIKE '%GYM%' THEN 'Gimnasio'
                        ELSE 'Otras Zonas'
                    END AS bloque
                ");
        }, 'sub')
        ->select('bloque', DB::raw('COUNT(*) as cantidad'))
        ->groupBy('bloque')
        ->orderBy('cantidad', 'desc')
        ->get();

        $estadisticasBloques = [];
        $bloqueTop = null;
        $totalRecorridosCount = LugarRecorrido::count();

        foreach ($bloquesRaw as $b) {
            $porcentaje = $totalRecorridosCount > 0 
                ? round(((int)$b->cantidad / $totalRecorridosCount) * 100, 1) 
                : 0;
            $item = [
                'bloque' => $b->bloque,
                'cantidad' => (int)$b->cantidad,
                'porcentaje' => $porcentaje,
            ];
            $estadisticasBloques[] = $item;
            if ($bloqueTop === null) {
                $bloqueTop = $item;
            }
        }

        // D. Ranking de Lugares Más Recorridos
        $rankingLugares = DB::table('tb_lugares_recorridos')
            ->select('lugar_mesh', DB::raw('COUNT(*) as total_visitas'))
            ->groupBy('lugar_mesh')
            ->orderBy('total_visitas', 'desc')
            ->limit(20)
            ->get();

        // E. Notificaciones del sistema
        $notificaciones = Notificacion::orderBy('id_notificacion', 'desc')->get();

        // F. Encuestas y métricas de satisfacción
        $encuestas = Encuesta::withCount('respuestas')->orderBy('id_encuesta', 'desc')->get();
        $totalRespuestasEncuestas = RespuestaEncuesta::count();
        $promedioGeneral = $totalRespuestasEncuestas > 0 ? round(RespuestaEncuesta::avg('calificacion_general'), 2) : 0;
        $promedioFacilidad = $totalRespuestasEncuestas > 0 ? round(RespuestaEncuesta::avg('facilidad_uso'), 2) : 0;
        $promedioMapa3D = $totalRespuestasEncuestas > 0 ? round(RespuestaEncuesta::avg('calidad_mapa_3d'), 2) : 0;

        $estrellasCounts = [
            1 => RespuestaEncuesta::where('calificacion_general', 1)->count(),
            2 => RespuestaEncuesta::where('calificacion_general', 2)->count(),
            3 => RespuestaEncuesta::where('calificacion_general', 3)->count(),
            4 => RespuestaEncuesta::where('calificacion_general', 4)->count(),
            5 => RespuestaEncuesta::where('calificacion_general', 5)->count(),
        ];

        $ultimosComentarios = RespuestaEncuesta::leftJoin('tb_usuario_general', 'tb_respuestas_encuesta.id_usuario', '=', 'tb_usuario_general.id_usuario')
            ->whereNotNull('tb_respuestas_encuesta.comentarios')
            ->where('tb_respuestas_encuesta.comentarios', '!=', '')
            ->select(
                'tb_respuestas_encuesta.id_respuesta',
                'tb_respuestas_encuesta.id_encuesta',
                'tb_respuestas_encuesta.id_usuario',
                'tb_respuestas_encuesta.calificacion_general',
                'tb_respuestas_encuesta.facilidad_uso',
                'tb_respuestas_encuesta.calidad_mapa_3d',
                'tb_respuestas_encuesta.comentarios',
                'tb_respuestas_encuesta.created_at',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos'
            )
            ->orderBy('tb_respuestas_encuesta.created_at', 'desc')
            ->limit(30)
            ->get();

        $encuestasStats = [
            'total_respuestas' => $totalRespuestasEncuestas,
            'promedio_general' => $promedioGeneral,
            'promedio_facilidad' => $promedioFacilidad,
            'promedio_mapa_3d' => $promedioMapa3D,
            'estrellas' => $estrellasCounts,
            'comentarios' => $ultimosComentarios,
        ];

        // G. Testers registrados para el entorno de pruebas
        $testers = Tester::orderBy('id_tester', 'desc')->get();

        return response()->json([
            'usuarios' => $usuarios,
            'sesiones' => $sesiones,
            'recorridos' => $recorridos,
            'reportes' => $reportes,
            'admins' => $admins,
            'admin_sesiones' => $adminSesiones,
            'admin_auditorias' => $adminAuditorias,
            'tipos_usuario' => $tipos,
            'ranking_lugares' => $rankingLugares,
            'notificaciones' => $notificaciones,
            'encuestas' => $encuestas,
            'encuestas_stats' => $encuestasStats,
            'testers' => $testers,
            'metricas' => [
                'total_usuarios' => $usuarios->count(),
                'total_sesiones' => InicioSesion::count(),
                'total_recorridos' => $totalRecorridosCount,
                'total_reportes' => $reportes->count(),
                'reportes_pendientes' => $reportes->where('estado', 'pendiente')->count(),
                'reportes_resueltos' => $reportes->where('estado', 'resuelto')->count(),
                'reportes_en_revision' => $reportes->where('estado', 'en_revision')->count(),
                'total_admins' => $admins->count(),
                'total_auditorias' => AdminAuditoria::count(),
                'total_notificaciones' => $notificaciones->count(),
                'total_notificaciones_activas' => $notificaciones->where('activo', true)->count(),
                'total_encuestas_respuestas' => $totalRespuestasEncuestas,
                'satisfaccion_promedio' => $promedioGeneral,
                'total_testers' => $testers->count(),
                'testers_activos' => $testers->where('activo', true)->count(),
                'mes_top' => $mesTop,
                'dia_top' => $diaTop,
                'bloque_top' => $bloqueTop,
            ],
            'estadisticas' => [
                'meses' => $estadisticasMeses,
                'dias_semana' => $estadisticasDias,
                'bloques' => $estadisticasBloques,
            ],
        ], 200);
    }

    /**
     * Actualizar estado de un reporte de error / bug con AUDITORÍA AUTOMÁTICA
     */
    public function actualizarEstadoReporte(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,en_revision,resuelto',
            'admin_username' => 'nullable|string',
            'id_admin' => 'nullable|integer',
            'nota_admin' => 'nullable|string',
        ]);

        $reporte = ReporteMapa::find($id);
        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }

        $estadoAnterior = $reporte->estado;
        $reporte->estado = $validated['estado'];
        $reporte->save();

        // Extraer identidad del admin
        $adminUsername = $validated['admin_username'] ?? 'admin@Unimap';
        $admin = Admin::where('username', $adminUsername)->first();
        $idAdmin = $admin ? $admin->id_admin : ($validated['id_admin'] ?? null);

        // Determinar tipo de acción descriptiva
        $tipoAccion = ($reporte->estado === 'resuelto') 
            ? 'RESOLUCION_BUG' 
            : 'CAMBIO_ESTADO_REPORTE';

        $descripcion = ($reporte->estado === 'resuelto')
            ? "El administrador '{$adminUsername}' resolvió la incidencia #{$reporte->id_reporte} ('{$reporte->tipo_error}' en '{$reporte->lugar_afectado}')."
            : "El administrador '{$adminUsername}' cambió el estado del reporte #{$reporte->id_reporte} de '{$estadoAnterior}' a '{$reporte->estado}'.";

        if (!empty($validated['nota_admin'])) {
            $descripcion .= " Nota: " . $validated['nota_admin'];
        }

        // Registrar en tb_admin_auditoria con TODOS los datos
        $auditoria = AdminAuditoria::create([
            'id_admin' => $idAdmin,
            'admin_username' => $adminUsername,
            'tipo_accion' => $tipoAccion,
            'modulo' => 'Reportes del Mapa',
            'descripcion' => $descripcion,
            'detalles_json' => [
                'id_reporte' => $reporte->id_reporte,
                'lugar_afectado' => $reporte->lugar_afectado,
                'tipo_error' => $reporte->tipo_error,
                'descripcion_original' => $reporte->descripcion,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $reporte->estado,
                'nota_admin' => $validated['nota_admin'] ?? null,
                'fecha_reporte' => $reporte->fecha_reporte,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Estado del reporte actualizado exitosamente',
            'reporte' => $reporte,
            'auditoria' => $auditoria,
        ], 200);
    }

    /**
     * Generar y descargar Reporte PDF adaptado a la sección activa o general
     */
    public function generarReportePDF(Request $request)
    {
        $seccion = $request->input('seccion', $request->query('seccion', 'general'));
        $adminUsername = $request->input('admin_username', $request->query('admin_username', 'admin@Unimap'));
        $graficas = $request->input('graficas', []);
        $fechaGeneracion = now()->format('d/m/Y H:i:s');
        $timestamp = now()->format('Ymd_His');

        $data = [
            'seccion' => $seccion,
            'admin_responsable' => $adminUsername,
            'fecha_generacion' => $fechaGeneracion,
            'graficas' => $graficas,
        ];

        $orientacion = 'portrait';
        $fileName = 'Reporte_Graficas_UniMap_' . $timestamp . '.pdf';

        switch ($seccion) {
            case 'usuarios':
                $totalUsuarios = UsuarioGeneral::count();
                $estudiantes = UsuarioGeneral::where('tipo_usuario', 2)->count();
                $visitantes = UsuarioGeneral::where('tipo_usuario', 1)->count();
                $otrosRoles = max(0, $totalUsuarios - $estudiantes - $visitantes);
                $nuevosMes = UsuarioGeneral::whereYear('fecha_sys', now()->year)->whereMonth('fecha_sys', now()->month)->count();

                $distribucionRoles = [
                    ['label' => 'Estudiantes ITFIP', 'cantidad' => $estudiantes, 'porcentaje' => $totalUsuarios > 0 ? round(($estudiantes / $totalUsuarios) * 100, 1) : 0, 'color' => '#2563eb'],
                    ['label' => 'Visitantes / Generales', 'cantidad' => $visitantes, 'porcentaje' => $totalUsuarios > 0 ? round(($visitantes / $totalUsuarios) * 100, 1) : 0, 'color' => '#10b981'],
                    ['label' => 'Otros Roles Institucionales', 'cantidad' => $otrosRoles, 'porcentaje' => $totalUsuarios > 0 ? round(($otrosRoles / $totalUsuarios) * 100, 1) : 0, 'color' => '#8b5cf6'],
                ];

                $driver = DB::connection()->getDriverName();
                $mesesSql = $driver === 'pgsql'
                    ? "TO_CHAR(fecha_sys, 'YYYY-MM') as mes_clave, COUNT(*) as cantidad"
                    : "DATE_FORMAT(fecha_sys, '%Y-%m') as mes_clave, COUNT(*) as cantidad";
                $mesesGroup = $driver === 'pgsql' ? DB::raw("TO_CHAR(fecha_sys, 'YYYY-MM')") : "mes_clave";

                $mesesRaw = DB::table('tb_usuario_general')
                    ->selectRaw($mesesSql)
                    ->whereNotNull('fecha_sys')
                    ->groupBy($mesesGroup)
                    ->orderBy('mes_clave', 'desc')
                    ->limit(6)
                    ->get();

                $distribucionMeses = [];
                foreach ($mesesRaw as $m) {
                    $distribucionMeses[] = [
                        'mes' => $m->mes_clave,
                        'cantidad' => (int)$m->cantidad,
                        'porcentaje' => $totalUsuarios > 0 ? round(((int)$m->cantidad / $totalUsuarios) * 100, 1) : 0,
                    ];
                }

                $data = array_merge($data, [
                    'titulo_seccion' => 'REPORTE GRÁFICO: USUARIOS Y ROLES EN EL SISTEMA',
                    'descripcion_seccion' => 'Análisis gráfico y visual de la comunidad universitaria, distribución de roles y evolución de nuevos registros.',
                    'kpis' => [
                        ['label' => 'Total Usuarios', 'valor' => $totalUsuarios, 'color' => '#0f172a'],
                        ['label' => 'Estudiantes ITFIP', 'valor' => $estudiantes, 'color' => '#2563eb'],
                        ['label' => 'Visitantes / Generales', 'valor' => $visitantes, 'color' => '#10b981'],
                        ['label' => 'Registros este Mes', 'valor' => $nuevosMes, 'color' => '#8b5cf6'],
                    ],
                    'distribucion_roles' => $distribucionRoles,
                    'distribucion_meses' => $distribucionMeses,
                ]);
                $fileName = 'Reporte_Graficas_Usuarios_UniMap_' . $timestamp . '.pdf';
                break;

            case 'sesiones':
                $totalSesiones = InicioSesion::count();
                $sesionesHoy = InicioSesion::whereDate('fecha_ingreso', now()->toDateString())->count();
                $sesionesMovil = InicioSesion::where(function($q) {
                    $q->where('dispositivo', 'LIKE', '%Android%')
                      ->orWhere('dispositivo', 'LIKE', '%iPhone%')
                      ->orWhere('dispositivo', 'LIKE', '%Mobile%');
                })->count();
                $sesionesPC = max(0, $totalSesiones - $sesionesMovil);

                $distribucionDispositivos = [
                    ['label' => 'Computador de Escritorio / PC', 'cantidad' => $sesionesPC, 'porcentaje' => $totalSesiones > 0 ? round(($sesionesPC / $totalSesiones) * 100, 1) : 0, 'color' => '#2563eb'],
                    ['label' => 'Dispositivos Móviles / Smartphones', 'cantidad' => $sesionesMovil, 'porcentaje' => $totalSesiones > 0 ? round(($sesionesMovil / $totalSesiones) * 100, 1) : 0, 'color' => '#f59e0b'],
                ];

                $driver = DB::connection()->getDriverName();
                $diasSql = $driver === 'pgsql'
                    ? "(EXTRACT(ISODOW FROM fecha_ingreso) - 1)::int as dia_num, COUNT(*) as cantidad"
                    : "WEEKDAY(fecha_ingreso) as dia_num, COUNT(*) as cantidad";
                $diasGroup = $driver === 'pgsql' ? DB::raw("(EXTRACT(ISODOW FROM fecha_ingreso) - 1)::int") : "dia_num";

                $diasRaw = DB::table('tb_inicio_sesion')
                    ->selectRaw($diasSql)
                    ->whereNotNull('fecha_ingreso')
                    ->groupBy($diasGroup)
                    ->get();
                $diasNombres = [0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles', 3 => 'Jueves', 4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo'];
                $distribucionDias = [];
                foreach ($diasNombres as $num => $nom) {
                    $row = $diasRaw->firstWhere('dia_num', $num);
                    $cant = $row ? (int)$row->cantidad : 0;
                    $porc = $totalSesiones > 0 ? round(($cant / $totalSesiones) * 100, 1) : 0;
                    $distribucionDias[] = ['dia' => $nom, 'cantidad' => $cant, 'porcentaje' => $porc];
                }

                $data = array_merge($data, [
                    'titulo_seccion' => 'REPORTE GRÁFICO: INICIOS DE SESIÓN Y ACCESIBILIDAD',
                    'descripcion_seccion' => 'Métricas gráficas de acceso temporal, distribución por días de la semana y plataformas empleadas por la comunidad.',
                    'kpis' => [
                        ['label' => 'Total Sesiones', 'valor' => $totalSesiones, 'color' => '#0f172a'],
                        ['label' => 'Sesiones Hoy', 'valor' => $sesionesHoy, 'color' => '#2563eb'],
                        ['label' => 'Accesos PC / Desktop', 'valor' => $sesionesPC, 'color' => '#10b981'],
                        ['label' => 'Accesos Móviles', 'valor' => $sesionesMovil, 'color' => '#f59e0b'],
                    ],
                    'distribucion_dispositivos' => $distribucionDispositivos,
                    'distribucion_dias' => $distribucionDias,
                ]);
                $fileName = 'Reporte_Graficas_Sesiones_UniMap_' . $timestamp . '.pdf';
                break;

            case 'recorridos':
                $totalRecorridos = LugarRecorrido::count();
                $lugaresUnicos = DB::table('tb_lugares_recorridos')->distinct('lugar_mesh')->count('lugar_mesh');
                $rankingLugares = DB::table('tb_lugares_recorridos')
                    ->select('lugar_mesh', DB::raw('COUNT(*) as total_visitas'))
                    ->groupBy('lugar_mesh')
                    ->orderBy('total_visitas', 'desc')
                    ->limit(8)
                    ->get();
                $lugarTop = $rankingLugares->first()?->lugar_mesh ?? 'Ninguno';

                $bloquesRaw = DB::table(function ($query) {
                    $query->from('tb_lugares_recorridos')
                        ->selectRaw("
                            CASE 
                                WHEN lugar_mesh LIKE '%-E%' THEN 'Bloque E'
                                WHEN lugar_mesh LIKE '%-D%' THEN 'Bloque D'
                                WHEN lugar_mesh LIKE '%-A%' THEN 'Bloque A'
                                WHEN lugar_mesh LIKE '%-B%' THEN 'Bloque B'
                                WHEN lugar_mesh LIKE '%-C%' THEN 'Bloque C'
                                WHEN lugar_mesh LIKE '%Cafeteria%' THEN 'Cafetería'
                                WHEN lugar_mesh LIKE '%Coliseo%' THEN 'Coliseo'
                                WHEN lugar_mesh LIKE '%GYM%' THEN 'Gimnasio'
                                ELSE 'Otras Zonas'
                            END AS bloque
                        ");
                }, 'sub')
                ->select('bloque', DB::raw('COUNT(*) as cantidad'))
                ->groupBy('bloque')
                ->orderBy('cantidad', 'desc')
                ->get();

                $distribucionBloques = [];
                foreach ($bloquesRaw as $b) {
                    $distribucionBloques[] = [
                        'bloque' => $b->bloque,
                        'cantidad' => (int)$b->cantidad,
                        'porcentaje' => $totalRecorridos > 0 ? round(((int)$b->cantidad / $totalRecorridos) * 100, 1) : 0,
                    ];
                }

                $data = array_merge($data, [
                    'titulo_seccion' => 'REPORTE GRÁFICO: NAVEGACIÓN Y LUGARES MÁS RECORRIDOS',
                    'descripcion_seccion' => 'Consolidado visual de demanda de espacios en el visor 3D, bloques con mayor afluencia y ranking de áreas.',
                    'kpis' => [
                        ['label' => 'Total Recorridos', 'valor' => $totalRecorridos, 'color' => '#0f172a'],
                        ['label' => 'Lugares Únicos', 'valor' => $lugaresUnicos, 'color' => '#2563eb'],
                        ['label' => 'Zona Más Visitada', 'valor' => substr($lugarTop, 0, 16), 'color' => '#10b981'],
                        ['label' => 'Visitas Lugar Top', 'valor' => $rankingLugares->first()?->total_visitas ?? 0, 'color' => '#f59e0b'],
                    ],
                    'ranking_lugares' => $rankingLugares,
                    'distribucion_bloques' => $distribucionBloques,
                ]);
                $fileName = 'Reporte_Graficas_Recorridos_UniMap_' . $timestamp . '.pdf';
                break;

            case 'reportes':
                $totalReportes = ReporteMapa::count();
                $resueltos = ReporteMapa::where('estado', 'resuelto')->count();
                $enRevision = ReporteMapa::where('estado', 'en_revision')->count();
                $pendientes = ReporteMapa::where('estado', 'pendiente')->count();

                $distribucionEstados = [
                    ['label' => 'Incidencias Resueltas', 'cantidad' => $resueltos, 'porcentaje' => $totalReportes > 0 ? round(($resueltos / $totalReportes) * 100, 1) : 0, 'color' => '#15803d'],
                    ['label' => 'Incidencias En Revisión', 'cantidad' => $enRevision, 'porcentaje' => $totalReportes > 0 ? round(($enRevision / $totalReportes) * 100, 1) : 0, 'color' => '#b45309'],
                    ['label' => 'Incidencias Pendientes', 'cantidad' => $pendientes, 'porcentaje' => $totalReportes > 0 ? round(($pendientes / $totalReportes) * 100, 1) : 0, 'color' => '#b91c1c'],
                ];

                $tiposRaw = DB::table('tb_reportes_mapa')
                    ->select('tipo_error', DB::raw('COUNT(*) as total'))
                    ->groupBy('tipo_error')
                    ->orderBy('total', 'desc')
                    ->get();

                $distribucionTipos = [];
                foreach ($tiposRaw as $t) {
                    $distribucionTipos[] = [
                        'tipo' => ucfirst($t->tipo_error ?? 'General'),
                        'cantidad' => (int)$t->total,
                        'porcentaje' => $totalReportes > 0 ? round(((int)$t->total / $totalReportes) * 100, 1) : 0,
                    ];
                }

                $data = array_merge($data, [
                    'titulo_seccion' => 'REPORTE GRÁFICO: INCIDENCIAS Y ESTADO DEL MAPA',
                    'descripcion_seccion' => 'Métricas visuales de resolución de errores reportados, balance de estados técnicos y tipos de falla registrados.',
                    'kpis' => [
                        ['label' => 'Total Reportes', 'valor' => $totalReportes, 'color' => '#0f172a'],
                        ['label' => 'Bugs Resueltos', 'valor' => $resueltos, 'color' => '#15803d'],
                        ['label' => 'En Revisión', 'valor' => $enRevision, 'color' => '#b45309'],
                        ['label' => 'Pendientes', 'valor' => $pendientes, 'color' => '#b91c1c'],
                    ],
                    'distribucion_estados' => $distribucionEstados,
                    'distribucion_tipos' => $distribucionTipos,
                ]);
                $fileName = 'Reporte_Graficas_Incidencias_UniMap_' . $timestamp . '.pdf';
                break;

            case 'admins':
                $admins = Admin::orderBy('id_admin', 'asc')->get();
                $totalAdmins = $admins->count();
                $superadmins = $admins->where('rol', 'superadmin')->count();
                $adminsReg = $admins->where('rol', 'administrador')->count();
                $activos = $admins->where('activo', true)->count();

                $data = array_merge($data, [
                    'titulo_seccion' => 'INFORME DE ADMINISTRADORES DEL SISTEMA',
                    'descripcion_seccion' => 'Listado oficial de cuentas de administración, privilegios asignados, estado y registro de creación.',
                    'kpis' => [
                        ['label' => 'Total Administradores', 'valor' => $totalAdmins, 'color' => '#0f172a'],
                        ['label' => 'Super Administradores', 'valor' => $superadmins, 'color' => '#8b5cf6'],
                        ['label' => 'Administradores Regulares', 'valor' => $adminsReg, 'color' => '#2563eb'],
                        ['label' => 'Cuentas Activas', 'valor' => $activos, 'color' => '#10b981'],
                    ],
                    'registros_admins' => $admins,
                ]);
                $fileName = 'Reporte_Administradores_UniMap_' . $timestamp . '.pdf';
                break;

            case 'auditorias':
                $auditorias = AdminAuditoria::orderBy('fecha_accion', 'desc')->limit(250)->get();
                $totalAuditorias = AdminAuditoria::count();
                $resolucionesBugs = AdminAuditoria::where('tipo_accion', 'RESOLUCION_BUG')->count();
                $cambiosEstado = AdminAuditoria::where('tipo_accion', 'CAMBIO_ESTADO_REPORTE')->count();
                $logins = AdminAuditoria::where('tipo_accion', 'LOGIN_ADMIN')->count();

                $data = array_merge($data, [
                    'titulo_seccion' => 'INFORME DE AUDITORÍA Y TRAZABILIDAD DE CAMBIOS',
                    'descripcion_seccion' => 'Registro inmutable de todas las acciones realizadas por administradores, resoluciones técnicas y accesos.',
                    'kpis' => [
                        ['label' => 'Eventos Auditados', 'valor' => $totalAuditorias, 'color' => '#0f172a'],
                        ['label' => 'Resoluciones de Bugs', 'valor' => $resolucionesBugs, 'color' => '#10b981'],
                        ['label' => 'Cambios de Estado', 'valor' => $cambiosEstado, 'color' => '#2563eb'],
                        ['label' => 'Inicios de Sesión', 'valor' => $logins, 'color' => '#64748b'],
                    ],
                    'registros_auditorias' => $auditorias,
                ]);
                $fileName = 'Reporte_Auditoria_Cambios_UniMap_' . $timestamp . '.pdf';
                break;

            case 'admin_sesiones':
                $adminSesiones = AdminSesion::orderBy('fecha_inicio', 'desc')->limit(200)->get();
                $totalSesionesAdmin = AdminSesion::count();
                $activas = AdminSesion::where('estado', 'activa')->count();
                $cerradas = AdminSesion::where('estado', 'cerrada')->count();
                $adminsConectados = DB::table('tb_admin_sesiones')->distinct('id_admin')->count('id_admin');

                $data = array_merge($data, [
                    'titulo_seccion' => 'INFORME DE SESIONES Y ACCESOS ADMINISTRATIVOS',
                    'descripcion_seccion' => 'Bitácora de seguridad y auditoría de accesos al panel administrativo, direcciones IP y tiempos de actividad.',
                    'kpis' => [
                        ['label' => 'Sesiones Registradas', 'valor' => $totalSesionesAdmin, 'color' => '#0f172a'],
                        ['label' => 'Sesiones Activas', 'valor' => $activas, 'color' => '#10b981'],
                        ['label' => 'Sesiones Cerradas', 'valor' => $cerradas, 'color' => '#64748b'],
                        ['label' => 'Admins Conectados', 'valor' => $adminsConectados, 'color' => '#2563eb'],
                    ],
                    'registros_admin_sesiones' => $adminSesiones,
                ]);
                $fileName = 'Reporte_Sesiones_Admin_UniMap_' . $timestamp . '.pdf';
                break;

            case 'testers':
                $testers = Tester::orderBy('id_tester', 'desc')->get();
                $totalTesters = $testers->count();
                $activos = $testers->where('activo', true)->count();
                $inactivos = $totalTesters - $activos;
                $conAcceso = $testers->whereNotNull('ultimo_acceso')->count();

                $data = array_merge($data, [
                    'titulo_seccion' => 'INFORME DE TESTERS Y ENTORNO DE PRUEBAS',
                    'descripcion_seccion' => 'Control de cuentas de prueba, validadores técnicos de calibración GPS y pruebas en WebGL.',
                    'kpis' => [
                        ['label' => 'Total Testers', 'valor' => $totalTesters, 'color' => '#0f172a'],
                        ['label' => 'Testers Activos', 'valor' => $activos, 'color' => '#10b981'],
                        ['label' => 'Testers Inactivos', 'valor' => $inactivos, 'color' => '#64748b'],
                        ['label' => 'Con Acceso Registrado', 'valor' => $conAcceso, 'color' => '#2563eb'],
                    ],
                    'registros_testers' => $testers,
                ]);
                $fileName = 'Reporte_Testers_UniMap_' . $timestamp . '.pdf';
                break;

            default: // General
                $auditorias = AdminAuditoria::orderBy('fecha_accion', 'desc')->limit(120)->get();
                $reportes = ReporteMapa::orderBy('fecha_reporte', 'desc')->limit(100)->get();
                $sesiones = AdminSesion::orderBy('fecha_inicio', 'desc')->limit(40)->get();

                $data = array_merge($data, [
                    'titulo_seccion' => 'INFORME EJECUTIVO GENERAL DEL SISTEMA UNIMAP 3D',
                    'descripcion_seccion' => 'Resumen global de actividad, usuarios, auditoría, sesiones y soporte técnico del campus 3D.',
                    'kpis' => [
                        ['label' => 'Acciones Auditadas', 'valor' => AdminAuditoria::count(), 'color' => '#0f172a'],
                        ['label' => 'Bugs Resueltos', 'valor' => ReporteMapa::where('estado', 'resuelto')->count(), 'color' => '#15803d'],
                        ['label' => 'En Revisión', 'valor' => ReporteMapa::where('estado', 'en_revision')->count(), 'color' => '#b45309'],
                        ['label' => 'Pendientes', 'valor' => ReporteMapa::where('estado', 'pendiente')->count(), 'color' => '#b91c1c'],
                    ],
                    'auditorias' => $auditorias,
                    'reportes' => $reportes,
                    'sesiones' => $sesiones,
                ]);
                $fileName = 'Reporte_General_UniMap_' . $timestamp . '.pdf';
                break;
        }

        $pdf = Pdf::loadView('pdf.reporte_admin', $data)
            ->setPaper('a4', $orientacion)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);

        return $pdf->download($fileName);
    }

    // ==============================================================
    // GESTIÓN DE NOTIFICACIONES (AVISOS Y MANTENIMIENTO)
    // ==============================================================
    public function crearNotificacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'mensaje' => 'required|string',
            'tipo' => 'required|in:mantenimiento,actualizacion,aviso,urgente',
            'activo' => 'nullable|boolean',
            'admin_username' => 'nullable|string',
        ]);

        $adminUsername = $validated['admin_username'] ?? 'admin@Unimap';
        $notificacion = Notificacion::create([
            'titulo' => $validated['titulo'],
            'mensaje' => $validated['mensaje'],
            'tipo' => $validated['tipo'],
            'activo' => $validated['activo'] ?? true,
            'creado_por' => $adminUsername,
        ]);

        // Auditoría
        AdminAuditoria::create([
            'admin_username' => $adminUsername,
            'tipo_accion' => 'CREAR_NOTIFICACION',
            'modulo' => 'Notificaciones',
            'descripcion' => "El administrador '{$adminUsername}' publicó el aviso '{$notificacion->titulo}' de tipo '{$notificacion->tipo}'.",
            'detalles_json' => [
                'id_notificacion' => $notificacion->id_notificacion,
                'titulo' => $notificacion->titulo,
                'tipo' => $notificacion->tipo,
                'activo' => $notificacion->activo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Notificación creada exitosamente.',
            'notificacion' => $notificacion,
        ], 201);
    }

    public function toggleNotificacion(Request $request, $id): JsonResponse
    {
        $notificacion = Notificacion::find($id);
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $notificacion->activo = !$notificacion->activo;
        $notificacion->save();

        $adminUsername = $request->input('admin_username', 'admin@Unimap');
        AdminAuditoria::create([
            'admin_username' => $adminUsername,
            'tipo_accion' => 'TOGGLE_NOTIFICACION',
            'modulo' => 'Notificaciones',
            'descripcion' => "El administrador '{$adminUsername}' " . ($notificacion->activo ? 'activó' : 'desactivó') . " la notificación #{$notificacion->id_notificacion} ('{$notificacion->titulo}').",
            'detalles_json' => [
                'id_notificacion' => $notificacion->id_notificacion,
                'activo' => $notificacion->activo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Estado de la notificación actualizado.',
            'notificacion' => $notificacion,
        ], 200);
    }

    public function eliminarNotificacion(Request $request, $id): JsonResponse
    {
        $notificacion = Notificacion::find($id);
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $titulo = $notificacion->titulo;
        $notificacion->delete();

        $adminUsername = $request->input('admin_username', 'admin@Unimap');
        AdminAuditoria::create([
            'admin_username' => $adminUsername,
            'tipo_accion' => 'ELIMINAR_NOTIFICACION',
            'modulo' => 'Notificaciones',
            'descripcion' => "El administrador '{$adminUsername}' eliminó la notificación #{$id} ('{$titulo}').",
            'detalles_json' => ['id_notificacion' => $id, 'titulo' => $titulo],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json(['mensaje' => 'Notificación eliminada correctamente.'], 200);
    }

    // ==============================================================
    // GESTIÓN DE ENCUESTAS DE SATISFACCIÓN
    // ==============================================================
    public function crearEncuesta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
            'admin_username' => 'nullable|string',
        ]);

        $adminUsername = $validated['admin_username'] ?? 'admin@Unimap';

        // Si se marca como activa, desactivar las anteriores para mantener 1 activa
        if (!empty($validated['activo'])) {
            Encuesta::where('activo', true)->update(['activo' => false]);
        }

        $encuesta = Encuesta::create([
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => $validated['activo'] ?? true,
        ]);

        AdminAuditoria::create([
            'admin_username' => $adminUsername,
            'tipo_accion' => 'CREAR_ENCUESTA',
            'modulo' => 'Encuestas',
            'descripcion' => "El administrador '{$adminUsername}' creó la encuesta de satisfacción '{$encuesta->titulo}'.",
            'detalles_json' => [
                'id_encuesta' => $encuesta->id_encuesta,
                'titulo' => $encuesta->titulo,
                'activo' => $encuesta->activo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Encuesta creada exitosamente.',
            'encuesta' => $encuesta,
        ], 201);
    }

    public function toggleEncuesta(Request $request, $id): JsonResponse
    {
        $encuesta = Encuesta::find($id);
        if (!$encuesta) {
            return response()->json(['message' => 'Encuesta no encontrada'], 404);
        }

        $nuevoEstado = !$encuesta->activo;

        // Si se activa, desactivar las demás
        if ($nuevoEstado) {
            Encuesta::where('id_encuesta', '!=', $id)->update(['activo' => false]);
        }

        $encuesta->activo = $nuevoEstado;
        $encuesta->save();

        $adminUsername = $request->input('admin_username', 'admin@Unimap');
        AdminAuditoria::create([
            'admin_username' => $adminUsername,
            'tipo_accion' => 'TOGGLE_ENCUESTA',
            'modulo' => 'Encuestas',
            'descripcion' => "El administrador '{$adminUsername}' " . ($encuesta->activo ? 'activó' : 'desactivó') . " la encuesta #{$encuesta->id_encuesta} ('{$encuesta->titulo}').",
            'detalles_json' => [
                'id_encuesta' => $encuesta->id_encuesta,
                'activo' => $encuesta->activo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Estado de la encuesta actualizado.',
            'encuesta' => $encuesta,
        ], 200);
    }

    public function obtenerEncuestasDashboard(): JsonResponse
    {
        $encuestas = Encuesta::withCount('respuestas')->orderBy('id_encuesta', 'desc')->get();
        $totalRespuestas = RespuestaEncuesta::count();
        $promedioGeneral = $totalRespuestas > 0 ? round(RespuestaEncuesta::avg('calificacion_general'), 2) : 0;
        $promedioFacilidad = $totalRespuestas > 0 ? round(RespuestaEncuesta::avg('facilidad_uso'), 2) : 0;
        $promedioMapa3D = $totalRespuestas > 0 ? round(RespuestaEncuesta::avg('calidad_mapa_3d'), 2) : 0;

        $estrellasCounts = [
            1 => RespuestaEncuesta::where('calificacion_general', 1)->count(),
            2 => RespuestaEncuesta::where('calificacion_general', 2)->count(),
            3 => RespuestaEncuesta::where('calificacion_general', 3)->count(),
            4 => RespuestaEncuesta::where('calificacion_general', 4)->count(),
            5 => RespuestaEncuesta::where('calificacion_general', 5)->count(),
        ];

        $ultimosComentarios = RespuestaEncuesta::leftJoin('tb_usuario_general', 'tb_respuestas_encuesta.id_usuario', '=', 'tb_usuario_general.id_usuario')
            ->whereNotNull('tb_respuestas_encuesta.comentarios')
            ->where('tb_respuestas_encuesta.comentarios', '!=', '')
            ->select(
                'tb_respuestas_encuesta.id_respuesta',
                'tb_respuestas_encuesta.id_encuesta',
                'tb_respuestas_encuesta.id_usuario',
                'tb_respuestas_encuesta.calificacion_general',
                'tb_respuestas_encuesta.facilidad_uso',
                'tb_respuestas_encuesta.calidad_mapa_3d',
                'tb_respuestas_encuesta.comentarios',
                'tb_respuestas_encuesta.created_at',
                'tb_usuario_general.nombres',
                'tb_usuario_general.apellidos'
            )
            ->orderBy('tb_respuestas_encuesta.created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'encuestas' => $encuestas,
            'metricas' => [
                'total_respuestas' => $totalRespuestas,
                'promedio_general' => $promedioGeneral,
                'promedio_facilidad' => $promedioFacilidad,
                'promedio_mapa_3d' => $promedioMapa3D,
                'estrellas' => $estrellasCounts,
            ],
            'comentarios' => $ultimosComentarios,
        ], 200);
    }
}
