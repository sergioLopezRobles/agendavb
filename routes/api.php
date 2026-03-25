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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// RUTAS PÚBLICAS (No requieren sesión)
Route::get('/planes',[PlanController::class,'verplanes'])->name('plan.verplanes');
Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

// RUTAS DE TWILIO (Deben ser públicas para el registro)
Route::post('/enviar-codigo', [TwilioController::class, 'enviarCodigo']);
Route::post('/verificar-codigo', [TwilioController::class, 'verificarCodigo']);

// RUTAS DEL MODULO CITASCLIENTES
Route::get('/citasclientes/{slug}',[CitasClientesController::class,'citasclientes']);
Route::post('/registrar-cita-cliente',[CitasClientesController::class,'registrarcitacliente']);
Route::post('/horarios-disponibles',[CitasClientesController::class,'horariosdisponibles']);

// RUTAS PRIVADAS (Requieren sesión iniciada)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user',[AuthController::class,'user']);
    Route::post('/logout',[AuthController::class,'logout']);

    //ruta dashboard
    Route::get('/dashboard',[DashboardController::class,'index']);

    // Rutas del Módulo de Negocios
    Route::get('/mis-negocios', [DashboardController::class, 'misNegocios']);
    Route::put('/negocios/{id}', [DashboardController::class, 'actualizarNegocio']);

    // RUTAS PARA EL CRUD DE SERVICIOS
    Route::get('/negocios/{id}/servicios', [ServicioController::class, 'obtenerServicios']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::put('/servicios/{id}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{id}', [ServicioController::class, 'destroy']);

    //RUTAS PARA MODULO TICKETS
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);

    //RUTA STRIPE PARA PAGAR PLAN
    Route::post('/registrar-plan-negocio', [NegocioController::class, 'registrarPlanNegocio']);

    // RUTAS CITASNEGOCIO
    Route::get('/citas-negocios', [CitasNegociosController::class, 'citasnegocios']);
    Route::get('/citas-negocios/{id_negocio}', [CitasNegociosController::class, 'obtenerCitasNegocio']);
});
