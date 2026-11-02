<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoContrller;
use App\Http\Controllers\UsuarioGeneralController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TesterController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas de Usuario y Catálogo
Route::get('/documento', [DocumentoContrller::class, 'index']);
Route::get('/datos-academicos', [UsuarioGeneralController::class, 'obtenerDatosAcademicos']);
Route::post('/registro', [UsuarioGeneralController::class, 'store']);
Route::post('/login', [UsuarioGeneralController::class, 'login']);
Route::post('/recorrido', [UsuarioGeneralController::class, 'registrarRecorrido']);
Route::post('/reporte-mapa', [UsuarioGeneralController::class, 'registrarReporte']);
Route::put('/usuario/{id_usuario}', [UsuarioGeneralController::class, 'actualizarPerfil']);
Route::get('/usuario/{id_usuario}/perfil', [UsuarioGeneralController::class, 'obtenerPerfil']);
Route::get('/usuario/{id_usuario}/estadisticas', [UsuarioGeneralController::class, 'estadisticasUsuario']);
Route::post('/usuario/solicitar-cambio-rol', [UsuarioGeneralController::class, 'solicitarCambioRol']);
Route::post('/usuario/confirmar-cambio-rol', [UsuarioGeneralController::class, 'verificarCambioRol']);
Route::get('/usuario/verificar-cambio-rol', [UsuarioGeneralController::class, 'verificarCambioRol']);

// Rutas del Panel Administrativo y Auditoría
Route::post('/admin/login', [AdminController::class, 'loginAdmin']);
Route::post('/admin/logout', [AdminController::class, 'logoutAdmin']);
Route::post('/admin/crear', [AdminController::class, 'crearAdmin']);
Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
Route::patch('/admin/reportes/{id}/estado', [AdminController::class, 'actualizarEstadoReporte']);
Route::match(['get', 'post'], '/admin/reportes/pdf', [AdminController::class, 'generarReportePDF']);

// Notificaciones (Avisos del Sistema / Mantenimiento)
Route::get('/notificaciones/activas', [UsuarioGeneralController::class, 'obtenerNotificacionesActivas']);
Route::post('/admin/notificaciones', [AdminController::class, 'crearNotificacion']);
Route::patch('/admin/notificaciones/{id}/toggle', [AdminController::class, 'toggleNotificacion']);
Route::delete('/admin/notificaciones/{id}', [AdminController::class, 'eliminarNotificacion']);

// Encuestas de Satisfacción
Route::get('/encuestas/activa', [UsuarioGeneralController::class, 'obtenerEncuestaActiva']);
Route::post('/encuestas/responder', [UsuarioGeneralController::class, 'responderEncuesta']);
Route::get('/admin/encuestas/dashboard', [AdminController::class, 'obtenerEncuestasDashboard']);
Route::post('/admin/encuestas', [AdminController::class, 'crearEncuesta']);
Route::patch('/admin/encuestas/{id}/toggle', [AdminController::class, 'toggleEncuesta']);

// Testers y Entorno de Pruebas Aislado
Route::post('/tester/login', [TesterController::class, 'login']);
Route::get('/admin/testers', [TesterController::class, 'listar']);
Route::post('/admin/testers', [TesterController::class, 'crear']);
Route::patch('/admin/testers/{id}/toggle', [TesterController::class, 'toggleEstado']);
Route::delete('/admin/testers/{id}', [TesterController::class, 'eliminar']);


