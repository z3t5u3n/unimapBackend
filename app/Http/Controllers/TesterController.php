<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Models\Tester;
use App\Models\Admin;
use App\Models\AdminAuditoria;

class TesterController extends Controller
{
    /**
     * Iniciar sesión de Tester para acceder a la Interfaz de Pruebas
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Por favor ingrese su usuario de tester.',
            'password.required' => 'Por favor ingrese su contraseña de tester.',
        ]);

        $tester = Tester::where('username', $validated['username'])->first();

        if (!$tester || !Hash::check($validated['password'], $tester->password)) {
            return response()->json([
                'message' => 'Credenciales de tester inválidas. Verifique su usuario y contraseña.',
            ], 401);
        }

        if (!$tester->activo) {
            return response()->json([
                'message' => 'Esta cuenta de tester se encuentra temporalmente inactiva. Contacte al administrador.',
            ], 403);
        }

        // Actualizar último acceso
        $tester->ultimo_acceso = now();
        $tester->save();

        // Registrar auditoría del acceso
        AdminAuditoria::create([
            'admin_username' => $tester->username,
            'tipo_accion' => 'LOGIN_TESTER',
            'modulo' => 'Entorno de Pruebas',
            'descripcion' => "Acceso al Entorno de Pruebas por el tester '{$tester->username}' ({$tester->nombre}).",
            'detalles_json' => [
                'id_tester' => $tester->id_tester,
                'ip' => $request->ip(),
                'dispositivo' => $request->userAgent(),
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Acceso autorizado al Entorno de Pruebas. ¡Bienvenido, ' . $tester->nombre . '!',
            'tester' => [
                'id_tester' => $tester->id_tester,
                'username' => $tester->username,
                'nombre' => $tester->nombre,
                'dispositivo' => $tester->dispositivo,
                'observaciones' => $tester->observaciones,
                'ultimo_acceso' => $tester->ultimo_acceso ? $tester->ultimo_acceso->format('d/m/Y H:i:s') : null,
            ],
        ], 200);
    }

    /**
     * Listar testers para el panel administrativo
     */
    public function listar(): JsonResponse
    {
        $testers = Tester::orderBy('id_tester', 'desc')->get();
        $total = $testers->count();
        $activos = $testers->where('activo', true)->count();
        $inactivos = $total - $activos;

        return response()->json([
            'testers' => $testers,
            'metricas' => [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
            ],
        ], 200);
    }

    /**
     * Crear un nuevo tester desde el panel de administración
     */
    public function crear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:tb_testers,username',
            'nombre' => 'required|string|max:150',
            'password' => 'required|string|min:4',
            'dispositivo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
            'admin_username' => 'nullable|string',
        ], [
            'username.unique' => 'Este nombre de usuario de tester ya se encuentra registrado.',
            'password.min' => 'La contraseña debe tener al menos 4 caracteres.',
        ]);

        $adminUsername = $validated['admin_username'] ?? 'admin@Unimap';
        $admin = Admin::where('username', $adminUsername)->first();

        $tester = Tester::create([
            'username' => trim($validated['username']),
            'nombre' => trim($validated['nombre']),
            'password' => Hash::make($validated['password']),
            'dispositivo' => $validated['dispositivo'] ?? 'Dispositivo Móvil / Web',
            'observaciones' => $validated['observaciones'] ?? 'Tester asignado para validación de mapa y GPS',
            'activo' => true,
        ]);

        // Registrar auditoría de creación de tester
        AdminAuditoria::create([
            'id_admin' => $admin ? $admin->id_admin : null,
            'admin_username' => $adminUsername,
            'tipo_accion' => 'CREAR_TESTER',
            'modulo' => 'Gestión Testers',
            'descripcion' => "El administrador '{$adminUsername}' registró al tester '{$tester->username}' ({$tester->nombre}).",
            'detalles_json' => [
                'id_tester' => $tester->id_tester,
                'username' => $tester->username,
                'dispositivo' => $tester->dispositivo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Tester registrado exitosamente.',
            'tester' => $tester,
        ], 201);
    }

    /**
     * Alternar estado activo/inactivo del tester
     */
    public function toggleEstado(Request $request, $id): JsonResponse
    {
        $tester = Tester::find($id);
        if (!$tester) {
            return response()->json(['message' => 'Tester no encontrado.'], 404);
        }

        $tester->activo = !$tester->activo;
        $tester->save();

        $adminUsername = $request->input('admin_username', 'admin@Unimap');
        $admin = Admin::where('username', $adminUsername)->first();

        AdminAuditoria::create([
            'id_admin' => $admin ? $admin->id_admin : null,
            'admin_username' => $adminUsername,
            'tipo_accion' => 'CAMBIO_ESTADO_TESTER',
            'modulo' => 'Gestión Testers',
            'descripcion' => "El administrador '{$adminUsername}' cambió el estado del tester '{$tester->username}' a " . ($tester->activo ? 'ACTIVO' : 'INACTIVO') . ".",
            'detalles_json' => [
                'id_tester' => $tester->id_tester,
                'username' => $tester->username,
                'nuevo_estado' => $tester->activo,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => 'Estado del tester actualizado a ' . ($tester->activo ? 'Activo' : 'Inactivo') . '.',
            'tester' => $tester,
        ], 200);
    }

    /**
     * Eliminar cuenta de tester
     */
    public function eliminar(Request $request, $id): JsonResponse
    {
        $tester = Tester::find($id);
        if (!$tester) {
            return response()->json(['message' => 'Tester no encontrado.'], 404);
        }

        $username = $tester->username;
        $nombre = $tester->nombre;
        $tester->delete();

        $adminUsername = $request->input('admin_username', 'admin@Unimap');
        $admin = Admin::where('username', $adminUsername)->first();

        AdminAuditoria::create([
            'id_admin' => $admin ? $admin->id_admin : null,
            'admin_username' => $adminUsername,
            'tipo_accion' => 'ELIMINAR_TESTER',
            'modulo' => 'Gestión Testers',
            'descripcion' => "El administrador '{$adminUsername}' eliminó la cuenta del tester '{$username}' ({$nombre}).",
            'detalles_json' => [
                'id_tester' => $id,
                'username' => $username,
            ],
            'ip_origen' => $request->ip(),
            'fecha_accion' => now(),
        ]);

        return response()->json([
            'message' => "Tester '{$username}' eliminado exitosamente.",
        ], 200);
    }
}
