<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\UsuarioGeneral;
use App\Models\InicioSesion;
use App\Models\LugarRecorrido;
use App\Models\ReporteMapa;
use App\Models\VerificacionRol;
use App\Models\Estudiante;
use App\Models\Carrera;
use App\Models\Semestre;
use App\Models\Notificacion;
use App\Models\Encuesta;
use App\Models\RespuestaEncuesta;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UsuarioGeneralController extends Controller
{
    // Método para registrar usuario nuevo
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'tipo_documento' => 'required|integer|exists:tb_documentos,id_documento',
                'numero_documento' => 'required|numeric|digits_between:8,10|unique:tb_usuario_general,numero_documento',
            ],
            [
                'numero_documento.digits_between' => 'El documento debe tener entre 8 y 10 dígitos.',
                'numero_documento.unique' => 'Este número ya está registrado.',
                'tipo_documento.exists' => 'Este tipo de documento no es válido.',
            ]
        );

        // Generar un id_usuario: prefijo 'unimap' + 10 caracteres alfanuméricos (números y letras)
        do {
            $idUsuario = 'unimap' . strtoupper(Str::random(10));
        } while (UsuarioGeneral::where('id_usuario', $idUsuario)->exists());

        $usuario = UsuarioGeneral::create([
            'id_usuario' => $idUsuario,
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'tipo_documento' => $validated['tipo_documento'],
            'numero_documento' => $validated['numero_documento'],
            'tipo_usuario' => 1,
            'fecha_sys' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Usuario registrado exitosamente',
            'usuario' => $usuario,
        ], 201);
    }

    // Método para iniciar sesión con registro en tb_inicio_sesion
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'numero_documento' => 'required',
        ], [
            'numero_documento.required' => 'Por favor ingrese su número de documento',
        ]);

        $usuario = UsuarioGeneral::where('numero_documento', $request->numero_documento)->first();

        if (!$usuario) {
            return response()->json([
                'mensaje' => 'El número de documento no se encuentra registrado.',
            ], 404);
        }

        // Registrar automáticamente el inicio de sesión en tb_inicio_sesion
        InicioSesion::create([
            'id_usuario' => $usuario->id_usuario,
            'ip_acceso' => $request->ip(),
            'dispositivo' => substr($request->userAgent() ?? 'Navegador Web', 0, 255),
            'fecha_ingreso' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Bienvenido ' . $usuario->nombres,
            'usuario' => $this->formatearUsuarioConDatos($usuario),
        ], 200);
    }

    /**
     * Helper para enriquecer usuario con datos académicos de tb_estudiante si aplica
     */
    private function formatearUsuarioConDatos($usuario)
    {
        if (!$usuario) return null;

        $datos = $usuario->toArray();

        $estudiante = Estudiante::with(['carrera', 'semestre'])
            ->where('id_usuario', $usuario->id_usuario)
            ->first();

        if ($estudiante) {
            $datos['nombre_carrera'] = $estudiante->carrera?->carrera ?? null;
            $datos['nombre_semestre'] = $estudiante->semestre?->semestre ?? null;
            $datos['id_carrera'] = $estudiante->id_carrera;
            $datos['id_semestre'] = $estudiante->id_semestre;
            if (empty($datos['correo_institucional']) && !empty($estudiante->correo_institucional)) {
                $datos['correo_institucional'] = $estudiante->correo_institucional;
            }
        } else {
            $datos['nombre_carrera'] = null;
            $datos['nombre_semestre'] = null;
        }

        $datos['nombre_tipo'] = ((int)$usuario->tipo_usuario === 2) ? 'Estudiante' : 'General';

        return $datos;
    }

    // Método para obtener el perfil completo y actualizado del usuario
    public function obtenerPerfil($idUsuario): JsonResponse
    {
        $usuario = UsuarioGeneral::where('id_usuario', $idUsuario)->first();

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado.'], 404);
        }

        return response()->json([
            'usuario' => $this->formatearUsuarioConDatos($usuario),
        ], 200);
    }

    // Método para registrar los lugares buscados para recorrer
    public function registrarRecorrido(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_usuario' => 'required|string|exists:tb_usuario_general,id_usuario',
            'lugar_mesh' => 'required|string|max:150',
            'origen_busqueda' => 'nullable|string|max:50',
        ]);

        $usuario = UsuarioGeneral::where('id_usuario', $validated['id_usuario'])->first();

        // RESTRICCIÓN: Para el usuario general (tipo_usuario = 1), no se permite registrar salones de clase
        if ($usuario && (int)$usuario->tipo_usuario === 1) {
            if (preg_match('/(salon|sal\xc3\xb3n|aula|laboratorio)/i', $validated['lugar_mesh'])) {
                return response()->json([
                    'mensaje' => 'Acceso restringido: Los usuarios generales solo pueden recorrer áreas de trámites y visita (Decanatura, Auditorio, Cafetería, Coliseo, Gimnasio, etc.).',
                ], 403);
            }
        }

        $recorrido = LugarRecorrido::create([
            'id_usuario' => $validated['id_usuario'],
            'lugar_mesh' => $validated['lugar_mesh'],
            'origen_busqueda' => $validated['origen_busqueda'] ?? 'buscador',
            'fecha_busqueda' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Recorrido registrado con éxito',
            'recorrido' => $recorrido,
        ], 201);
    }

    // Método para actualizar los datos personales del usuario
    public function actualizarPerfil(Request $request, $idUsuario): JsonResponse
    {
        $usuario = UsuarioGeneral::where('id_usuario', $idUsuario)->first();

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado.'], 404);
        }

        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'numero_documento' => 'nullable|numeric|digits_between:8,10|unique:tb_usuario_general,numero_documento,' . $usuario->id_general . ',id_general',
            'tipo_documento' => 'nullable|integer|exists:tb_documentos,id_documento',
        ], [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'numero_documento.unique' => 'Este número de documento ya está en uso por otro usuario.',
            'numero_documento.digits_between' => 'El documento debe tener entre 8 y 10 dígitos.',
            'tipo_documento.exists' => 'Tipo de documento no válido.',
        ]);

        $usuario->nombres = $validated['nombres'];
        $usuario->apellidos = $validated['apellidos'];
        if (isset($validated['numero_documento'])) {
            $usuario->numero_documento = $validated['numero_documento'];
        }
        if (isset($validated['tipo_documento'])) {
            $usuario->tipo_documento = $validated['tipo_documento'];
        }
        $usuario->save();

        return response()->json([
            'mensaje' => 'Tus datos han sido actualizados con éxito.',
            'usuario' => $this->formatearUsuarioConDatos($usuario),
        ], 200);
    }

    // Método para obtener estadísticas e historial personal del usuario
    public function estadisticasUsuario($idUsuario): JsonResponse
    {
        $usuario = UsuarioGeneral::where('id_usuario', $idUsuario)->first();

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado.'], 404);
        }

        $totalSesiones = InicioSesion::where('id_usuario', $idUsuario)->count();
        $totalRecorridos = LugarRecorrido::where('id_usuario', $idUsuario)->count();

        // Lugar más buscado por este usuario
        $lugarTop = LugarRecorrido::where('id_usuario', $idUsuario)
            ->selectRaw('lugar_mesh, COUNT(*) as cantidad')
            ->groupBy('lugar_mesh')
            ->orderBy('cantidad', 'desc')
            ->first();

        // Bloque más visitado por este usuario (compatible con PostgreSQL y MySQL)
        $bloqueTop = DB::table(function ($query) use ($idUsuario) {
            $query->from('tb_lugares_recorridos')
                ->where('id_usuario', $idUsuario)
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
                        ELSE 'Área de Trámites / Visita'
                    END AS bloque
                ");
        }, 'sub')
        ->select('bloque', DB::raw('COUNT(*) as cantidad'))
        ->groupBy('bloque')
        ->orderBy('cantidad', 'desc')
        ->first();

        // Últimas sesiones (hasta 15)
        $sesiones = InicioSesion::where('id_usuario', $idUsuario)
            ->orderBy('fecha_ingreso', 'desc')
            ->limit(15)
            ->get();

        // Últimos recorridos (hasta 15)
        $recorridos = LugarRecorrido::where('id_usuario', $idUsuario)
            ->orderBy('fecha_busqueda', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'usuario' => $usuario,
            'metricas' => [
                'total_sesiones' => $totalSesiones,
                'total_recorridos' => $totalRecorridos,
                'lugar_mas_buscado' => $lugarTop ? $lugarTop->lugar_mesh : 'Sin recorridos',
                'veces_lugar_top' => $lugarTop ? (int)$lugarTop->cantidad : 0,
                'bloque_favorito' => $bloqueTop ? $bloqueTop->bloque : 'Sin visitas',
            ],
            'sesiones' => $sesiones,
            'recorridos' => $recorridos,
        ], 200);
    }

    // Método para registrar reportes de errores/bugs del mapa 3D
    public function registrarReporte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_usuario' => 'required|string|exists:tb_usuario_general,id_usuario',
            'tipo_error' => 'required|string|max:100',
            'lugar_afectado' => 'nullable|string|max:150',
            'descripcion' => 'required|string',
        ], [
            'id_usuario.required' => 'El identificador de usuario es obligatorio.',
            'id_usuario.exists' => 'El usuario no existe.',
            'tipo_error.required' => 'Debe seleccionar un tipo de error.',
            'descripcion.required' => 'Debe ingresar una descripción del problema.',
        ]);

        $reporte = ReporteMapa::create([
            'id_usuario' => $validated['id_usuario'],
            'tipo_error' => $validated['tipo_error'],
            'lugar_afectado' => $validated['lugar_afectado'] ?? null,
            'descripcion' => $validated['descripcion'],
            'estado' => 'pendiente',
            'fecha_reporte' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Reporte enviado con éxito. Nuestro equipo revisará el error.',
            'reporte' => $reporte,
        ], 201);
    }

    // Obtener catálogo de carreras y semestres para formulario de estudiante
    public function obtenerDatosAcademicos(): JsonResponse
    {
        $carreras = Carrera::orderBy('carrera', 'asc')->get();
        $semestres = Semestre::orderBy('id_semestres', 'asc')->get();

        return response()->json([
            'carreras' => $carreras,
            'semestres' => $semestres,
        ], 200);
    }

    // Método para solicitar cambio de rol a Estudiante mediante correo @itfip.edu.co (vigencia 5 minutos)
    public function solicitarCambioRol(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_usuario' => 'required|string|exists:tb_usuario_general,id_usuario',
            'correo_institucional' => [
                'required',
                'string',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@(itfip|uniespinal)\.edu\.co$/i',
            ],
            'id_carrera' => 'required|integer|exists:tb_carreras,id_carrera',
            'id_semestre' => 'required|integer|exists:tb_semestre,id_semestres',
        ], [
            'id_usuario.required' => 'El identificador de usuario es obligatorio.',
            'id_usuario.exists' => 'El usuario no existe.',
            'correo_institucional.required' => 'El correo institucional es obligatorio.',
            'correo_institucional.email' => 'El formato del correo electrónico no es válido.',
            'correo_institucional.regex' => 'El correo debe ser institucional con terminación @itfip.edu.co o @uniespinal.edu.co',
            'id_carrera.required' => 'Debe seleccionar su programa académico / carrera.',
            'id_carrera.exists' => 'La carrera seleccionada no es válida.',
            'id_semestre.required' => 'Debe seleccionar su semestre actual.',
            'id_semestre.exists' => 'El semestre seleccionado no es válido.',
        ]);

        $usuario = UsuarioGeneral::where('id_usuario', $validated['id_usuario'])->first();

        if ((int)$usuario->tipo_usuario === 2) {
            return response()->json([
                'mensaje' => 'El usuario ya cuenta con el rol de Estudiante activo.',
            ], 400);
        }

        // Comprobar si el correo ya está verificado y asignado a otro usuario
        $correoOcupado = UsuarioGeneral::where('correo_institucional', $validated['correo_institucional'])
            ->where('id_usuario', '!=', $usuario->id_usuario)
            ->exists();

        if ($correoOcupado) {
            return response()->json([
                'mensaje' => 'Este correo institucional ya está vinculado a otra cuenta.',
            ], 422);
        }

        // Invalidar solicitudes pendientes anteriores para este usuario
        VerificacionRol::where('id_usuario', $usuario->id_usuario)
            ->where('utilizado', 0)
            ->update(['utilizado' => 1]);

        // Generar token único y seguro
        $token = Str::random(60);
        $expiraEn = now()->addMinutes(5);

        $verificacion = VerificacionRol::create([
            'id_usuario' => $usuario->id_usuario,
            'correo_institucional' => strtolower($validated['correo_institucional']),
            'nuevo_tipo_usuario' => 2, // Estudiante
            'id_carrera' => $validated['id_carrera'],
            'id_semestre' => $validated['id_semestre'],
            'token' => $token,
            'expira_en' => $expiraEn,
            'utilizado' => 0,
            'fecha_creacion' => now(),
        ]);

        // Construir enlace de verificación hacia el frontend en producción
        $urlFrontend = env('FRONTEND_URL', 'https://unimap.zetsuen33.workers.dev');
        $enlaceVerificacion = "{$urlFrontend}/VerificarRol?token={$token}";

        $carreraObj = Carrera::find($validated['id_carrera']);
        $semestreObj = Semestre::find($validated['id_semestre']);

        // Enviar correo electrónico con formato institucional ITFIP
        $datosCorreo = [
            'nombres' => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'correo' => $verificacion->correo_institucional,
            'carrera' => $carreraObj ? $carreraObj->carrera : 'Programa Académico',
            'semestre' => $semestreObj ? $semestreObj->semestre : '',
            'enlace' => $enlaceVerificacion,
            'fecha_expiracion' => $expiraEn->format('d/m/Y H:i:s'),
        ];

        try {
            Mail::send([], [], function ($message) use ($verificacion, $datosCorreo) {
                $cuerpoHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #0b3c5d; padding: 20px; text-align: center; color: #ffffff;'>
                        <h1 style='margin: 0; font-size: 24px; letter-spacing: 1px;'>UniMap - ITFIP</h1>
                        <p style='margin: 5px 0 0; font-size: 14px; opacity: 0.9;'>Instituto Tolimense de Formación Técnica Profesional / Universidad de El Espinal</p>
                    </div>
                    <div style='padding: 30px; background-color: #ffffff;'>
                        <h2 style='color: #0b3c5d; margin-top: 0;'>Confirmación de Cambio a Rol Estudiante</h2>
                        <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>
                            Hola <strong>{$datosCorreo['nombres']} {$datosCorreo['apellidos']}</strong>,
                        </p>
                        <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>
                            Has solicitado activar tu cuenta institucional como <strong>Estudiante</strong> en el mapa 3D interactivo UniMap con los siguientes datos académicos:
                        </p>
                        <div style='background-color: #edf2f7; padding: 14px 18px; border-radius: 8px; color: #2d3748; margin-bottom: 20px;'>
                            <p style='margin: 0 0 6px 0;'><strong>Correo Institucional:</strong> {$datosCorreo['correo']}</p>
                            <p style='margin: 0 0 6px 0;'><strong>Carrera:</strong> {$datosCorreo['carrera']}</p>
                            <p style='margin: 0;'><strong>Semestre:</strong> {$datosCorreo['semestre']}</p>
                        </div>
                        <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>
                            Al confirmar este cambio, obtendrás acceso inmediato a <strong>todos los salones, aulas, laboratorios y recorridos</strong> de los bloques del campus.
                        </p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$datosCorreo['enlace']}' style='background-color: #3182ce; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);'>
                                🎓 Confirmar Cambio a Estudiante
                            </a>
                        </div>
                        <div style='background-color: #fffaf0; border-left: 4px solid #dd6b20; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;'>
                            <p style='margin: 0; color: #9c4221; font-size: 13px;'>
                                ⚠️ <strong>Tiempo límite:</strong> Este enlace caducará automáticamente en <strong>5 minutos</strong> ({$datosCorreo['fecha_expiracion']}). Si no confirmas antes de este lapso, deberás solicitar uno nuevo.
                            </p>
                        </div>
                        <p style='color: #a0aec0; font-size: 12px; margin-top: 30px;'>
                            Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.
                        </p>
                    </div>
                    <div style='background-color: #f7fafc; padding: 15px; text-align: center; font-size: 12px; color: #718096; border-top: 1px solid #edf2f7;'>
                        UniMap ITFIP &bull; Campus Universitario El Espinal, Tolima
                    </div>
                </div>
                ";

                $message->to($verificacion->correo_institucional)
                    ->subject('🎓 Confirmación de Cambio de Rol a Estudiante - UniMap ITFIP')
                    ->html($cuerpoHtml);
            });
        } catch (\Throwable $th) {
            Log::error("Error enviando correo de verificación de rol: " . $th->getMessage());
        }

        return response()->json([
            'mensaje' => 'Se ha enviado un enlace de verificación a ' . $verificacion->correo_institucional . '. Revisa tu bandeja de entrada o carpeta de spam para confirmar que el correo está activo y registrado. Tienes 5 minutos para abrir el enlace.',
            'expira_en' => $expiraEn->toIso8601String(),
            'minutos_validez' => 5,
        ], 200);
    }

    // Método para confirmar y aplicar el cambio de rol con token de 5 minutos
    public function verificarCambioRol(Request $request)
    {
        $urlFrontend = env('FRONTEND_URL', 'https://unimap.zetsuen33.workers.dev');
        $token = $request->input('token') ?? $request->query('token');

        if (!$token) {
            if ($request->isMethod('get') && !$request->wantsJson()) {
                return redirect("{$urlFrontend}/VerificarRol?error=token_invalido");
            }
            return response()->json([
                'mensaje' => 'Token de verificación no proporcionado.',
            ], 400);
        }

        $verificacion = VerificacionRol::where('token', $token)->first();

        if (!$verificacion) {
            if ($request->isMethod('get') && !$request->wantsJson()) {
                return redirect("{$urlFrontend}/VerificarRol?error=token_invalido");
            }
            return response()->json([
                'mensaje' => 'El enlace de verificación no es válido.',
            ], 404);
        }

        if ($verificacion->utilizado) {
            if ($request->isMethod('get') && !$request->wantsJson()) {
                return redirect("{$urlFrontend}/VerificarRol?error=token_utilizado");
            }
            return response()->json([
                'mensaje' => 'Este enlace de verificación ya ha sido utilizado anteriormente.',
            ], 400);
        }

        // Validar expiración de 5 minutos
        if (now()->gt($verificacion->expira_en)) {
            if ($request->isMethod('get') && !$request->wantsJson()) {
                return redirect("{$urlFrontend}/VerificarRol?error=token_expirado");
            }
            return response()->json([
                'mensaje' => 'El enlace de verificación ha expirado. El tiempo máximo permitido es de 5 minutos. Por favor solicita uno nuevo desde tu perfil.',
                'expirado' => true,
            ], 410);
        }

        $usuario = UsuarioGeneral::where('id_usuario', $verificacion->id_usuario)->first();

        if (!$usuario) {
            return response()->json([
                'mensaje' => 'Usuario no encontrado.',
            ], 404);
        }

        // 1. Actualizar usuario a Estudiante (tipo_usuario = 2) y guardar su correo institucional
        $usuario->tipo_usuario = 2;
        $usuario->correo_institucional = $verificacion->correo_institucional;
        $usuario->save();

        // 2. Guardar registro en la tabla extendida tb_estudiante
        $estudiante = Estudiante::updateOrCreate(
            ['id_usuario' => $usuario->id_usuario],
            [
                'correo_institucional' => $verificacion->correo_institucional,
                'id_carrera' => $verificacion->id_carrera,
                'id_semestre' => $verificacion->id_semestre,
                'fecha_registro' => now(),
            ]
        );

        // Cargar relaciones para retornar información académica
        $estudiante->load(['carrera', 'semestre']);

        // 3. Marcar token como utilizado
        $verificacion->utilizado = 1;
        $verificacion->save();

        if ($request->isMethod('get') && !$request->wantsJson()) {
            return redirect("{$urlFrontend}/VerificarRol?token={$token}&exito=1");
        }

        return response()->json([
            'mensaje' => '¡Felicitaciones! Tu rol ha sido actualizado a Estudiante exitosamente. Ya tienes acceso a todos los salones.',
            'usuario' => $this->formatearUsuarioConDatos($usuario),
            'estudiante' => $estudiante,
        ], 200);
    }

    // ==============================================================
    // NOTIFICACIONES Y COMUNICADOS PARA USUARIOS
    // ==============================================================
    public function obtenerNotificacionesActivas(): JsonResponse
    {
        $notificaciones = Notificacion::where('activo', true)
            ->orderBy('id_notificacion', 'desc')
            ->get();

        return response()->json([
            'notificaciones' => $notificaciones,
            'total' => $notificaciones->count(),
        ], 200);
    }

    // ==============================================================
    // ENCUESTAS DE SATISFACCIÓN PARA USUARIOS
    // ==============================================================
    public function obtenerEncuestaActiva(Request $request): JsonResponse
    {
        $idUsuario = $request->query('id_usuario');
        $encuesta = Encuesta::where('activo', true)
            ->orderBy('id_encuesta', 'desc')
            ->first();

        if (!$encuesta) {
            return response()->json([
                'activa' => false,
                'encuesta' => null,
                'ha_respondido' => false,
            ], 200);
        }

        $haRespondido = false;
        if ($idUsuario) {
            $haRespondido = RespuestaEncuesta::where('id_encuesta', $encuesta->id_encuesta)
                ->where('id_usuario', $idUsuario)
                ->exists();
        }

        return response()->json([
            'activa' => true,
            'encuesta' => $encuesta,
            'ha_respondido' => $haRespondido,
        ], 200);
    }

    public function responderEncuesta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_encuesta' => 'required|integer|exists:tb_encuestas,id_encuesta',
            'id_usuario' => 'required|string',
            'calificacion_general' => 'required|integer|min:1|max:5',
            'facilidad_uso' => 'required|integer|min:1|max:5',
            'calidad_mapa_3d' => 'required|integer|min:1|max:5',
            'comentarios' => 'nullable|string|max:1000',
        ]);

        // Evitar que el mismo usuario responda dos veces la misma encuesta
        $yaRespondio = RespuestaEncuesta::where('id_encuesta', $validated['id_encuesta'])
            ->where('id_usuario', $validated['id_usuario'])
            ->exists();

        if ($yaRespondio) {
            return response()->json([
                'mensaje' => 'Ya has enviado tu respuesta para esta encuesta. ¡Muchas gracias por tu valiosa opinión!',
            ], 400);
        }

        $respuesta = RespuestaEncuesta::create([
            'id_encuesta' => $validated['id_encuesta'],
            'id_usuario' => $validated['id_usuario'],
            'calificacion_general' => $validated['calificacion_general'],
            'facilidad_uso' => $validated['facilidad_uso'],
            'calidad_mapa_3d' => $validated['calidad_mapa_3d'],
            'comentarios' => $validated['comentarios'] ?? null,
        ]);

        return response()->json([
            'mensaje' => '¡Encuesta enviada exitosamente! Tu valoración ayuda a mejorar UniMap 3D.',
            'respuesta' => $respuesta,
        ], 201);
    }
}

