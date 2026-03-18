<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\TwilioController;
use App\Http\Controllers\clientes\CitasClientesController;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\plan\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\servicios\ServicioController;
use App\Http\Controllers\ticket\TicketController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/planes',[PlanController::class,'verplanes'])->name('plan.verplanes');

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

// RUTAS DEL MODULO CITASCLIENTES
Route::get('/citasclientes/{slug}',[CitasClientesController::class,'citasclientes']);
Route::post('/registrar-cita-cliente',[CitasClientesController::class,'registrarcitacliente']);
Route::post('/horarios-disponibles',[CitasClientesController::class,'horariosdisponibles']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user',[AuthController::class,'user']);
    Route::post('/logout',[AuthController::class,'logout']);

    // Rutas de Twilio
    Route::post('/enviar-codigo', [TwilioController::class, 'enviarCodigo']);
    Route::post('/verificar-codigo', [TwilioController::class, 'verificarCodigo']);

    //ruta dashboard
    Route::get('/dashboard',[DashboardController::class,'index']);
    Route::post('/registrar-plan-negocio', [DashboardController::class, 'registrarPlanNegocio']);

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

    //PUT para conectar Vue con el nuevo update
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
});
