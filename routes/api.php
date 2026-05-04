<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\TwilioController;
use App\Http\Controllers\citasnegocios\CitasNegociosController;
use App\Http\Controllers\clientes\CitasClientesController;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\plan\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\servicios\ServicioController;
use App\Http\Controllers\ticket\TicketController;
use App\Http\Controllers\stripecard\NegocioController;
use App\Http\Controllers\admin\UsuarioAdminController;
use App\Http\Controllers\admin\AuditoriaController;
use App\Http\Controllers\admin\NegocioAdminController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\admin\ChatAdminController;
use App\Http\Controllers\ProfileController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// RUTAS PÚBLICAS
Route::get('/planes',[PlanController::class,'verplanes'])->name('plan.verplanes');
Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::post('/enviar-codigo', [TwilioController::class, 'enviarCodigo']);
Route::post('/verificar-codigo', [TwilioController::class, 'verificarCodigo']);

Route::get('/citasclientes/{slug}',[CitasClientesController::class,'citasclientes']);
Route::post('/registrar-cita-cliente',[CitasClientesController::class,'registrarcitacliente']);
Route::post('/horarios-disponibles',[CitasClientesController::class,'horariosdisponibles']);

// ZONA COMPARTIDA (Entran Administradores y Dueños)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user',[AuthController::class,'user']);
    Route::post('/logout',[AuthController::class,'logout']);
    Route::get('/dashboard',[DashboardController::class,'index']);

    // Ruta para actualizar perfil (Cualquier usuario loggeado puede hacerlo)
    Route::post('/perfil/actualizar', [ProfileController::class, 'update']);
});

// ZONA DUEÑOS (Exclusivo Rol 2)
Route::middleware(['auth:sanctum', 'role:2'])->group(function () {
    // Negocios y Servicios
    Route::get('/mis-negocios', [DashboardController::class, 'misNegocios']);
    Route::put('/negocios/{id}', [DashboardController::class, 'actualizarNegocio']);
    Route::get('/negocios/{id}/servicios', [ServicioController::class, 'obtenerServicios']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::put('/servicios/{id}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{id}', [ServicioController::class, 'destroy']);

    // Citas y Excel
    Route::get('/citas-negocios', [CitasNegociosController::class, 'citasnegocios']);
    Route::get('/citas-negocios/descargar-excel', [CitasNegociosController::class, 'descargarExcel']);
    Route::get('/citas-negocios/{id_negocio}', [CitasNegociosController::class, 'obtenerCitasNegocio']);
    Route::post('/actualizar-estado-cita', [CitasNegociosController::class, 'actualizarEstadoCita']);

    // Suscripciones y Extra
    Route::post('/registrar-plan-negocio', [NegocioController::class, 'registrarPlanNegocio']);
    Route::post('/upgrade-plan', [NegocioController::class, 'upgradePlanStripe']);
    Route::post('/negocios-extra', [NegocioController::class, 'crearNegocioExtra']);

    // Tickets Dueño (Solo crear y ver los suyos)
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
});

// ZONA ADMINISTRADOR (Exclusivo Rol 1)
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {
    // Tickets Admin
    Route::get('/admin/tickets', [TicketController::class, 'indexAdmin']);
    Route::put('/admin/tickets/{id}', [TicketController::class, 'update']);

    // Gestión de Usuarios Admin
    Route::get('/admin/usuarios', [UsuarioAdminController::class, 'index']);
    Route::put('/admin/usuarios/{id}', [UsuarioAdminController::class, 'update']);

    // Auditoría del Sistema
    Route::get('/admin/auditoria', [AuditoriaController::class, 'index']);

    // Gestión de Negocios Globales Admin
    Route::get('/admin/negocios', [NegocioAdminController::class, 'index']);

    // Citas de un negocio específico para Admin
    Route::get('/admin/negocios/{id}/citas', [NegocioAdminController::class, 'obtenerCitasNegocio']);

    // Servicios de un negocio específico para Admin
    Route::get('/admin/negocios/{id}/servicios', [NegocioAdminController::class, 'obtenerServiciosNegocio']);

    // Ruta de estadisticas por negocio
    Route::get('/admin/negocios/{id}/stats', [NegocioAdminController::class, 'obtenerEstadisticasNegocio']);

    // Chat Interno Admin (Direct Messages)
    Route::get('/admin/chat/contactos', [ChatAdminController::class, 'getContactos']);
    Route::get('/admin/chat/conversacion/{id}', [ChatAdminController::class, 'getConversacion']);
    Route::post('/admin/chat/enviar', [ChatAdminController::class, 'storePrivado']);
});

Route::get('/ver-logo/{nombre}', function($nombre) {
    $path = base_path('../uploads/documentos/imagenes/' . $nombre);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
