<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Clases\GlobalFuncion;
use App\Traits\RegistraMovimientos;

class DashboardController extends Controller
{
    use RegistraMovimientos;

    public function index(){
        $idUsuario = Auth::id();

        $usuarioLoggeado = DB::table('users as u')
            ->leftJoin('plan_usuarios as pu', 'u.id', '=', 'pu.id_usuario')
            ->leftJoin('roles_usuarios as ru', 'u.id', '=', 'ru.id_usuario')
            ->select('u.id', 'pu.id_plan', 'u.email', 'u.name', 'u.telefono', 'u.avatar', 'ru.id_rol')
            ->where('u.id', $idUsuario)
            ->first();

        if ($usuarioLoggeado && $usuarioLoggeado->id_rol == null) {
            $usuarioLoggeado->id_rol = 2;
        }

        if($usuarioLoggeado != null){

            // =========================================================
            // LÓGICA PARA EL ADMINISTRADOR (ROL 1)
            // =========================================================
            if ($usuarioLoggeado->id_rol == 1) {
                // ... (Tu código de MRR, Citas y Auditoría se queda exactamente igual)
                $mrr = DB::table('plan_usuarios')
                    ->join('caracteristicasplanes', 'plan_usuarios.id_plan', '=', 'caracteristicasplanes.id_plan')
                    ->where('caracteristicasplanes.titulo', 'precio')
                    ->sum(DB::raw('CAST(caracteristicasplanes.valor AS DECIMAL(10,2))'));

                $totalCitas = DB::table('citas')->count();

                $logsData = DB::table('movimientos_usuarios as m')
                    ->join('users as u', 'm.id_usuario', '=', 'u.id')
                    ->select('m.id', 'u.name as usuario_nombre', 'm.cambios', 'm.tipo_mensaje', DB::raw("DATE_FORMAT(m.created_at, '%d/%m/%Y %H:%i') as fecha"))
                    ->orderBy('m.created_at', 'desc')
                    ->limit(15)
                    ->get()
                    ->map(function ($log) {
                        $log->cambios = json_decode($log->cambios);
                        return $log;
                    });

                $statsAdmin = [
                    'total_usuarios' => DB::table('users')->count(),
                    'total_negocios' => DB::table('negocios')->count(),
                    'suscripciones_activas' => DB::table('plan_usuarios')->count(),
                    'tickets_pendientes' => DB::table('ticket_soporte_usuarios_negocios')->whereIn('id_estado', [1, 2])->count(),
                    'ingresos_mrr' => $mrr,
                    'total_citas' => $totalCitas,
                    'logs' => $logsData
                ];

                return response()->json([
                    'valid' => true,
                    'usuarioLoggeado' => $usuarioLoggeado,
                    'planAdquirido' => null,
                    'statsAdmin' => $statsAdmin
                ]);
            }

            // =========================================================
            // LÓGICA PARA EL DUEÑO (ROL 2)
            // =========================================================
            $cantidadNegocios = DB::table('negocios')->where('id_usuario', $idUsuario)->count();

            $hoy = \Carbon\Carbon::now()->format('Y-m-d');
            $citasHoy = DB::table('citas')
                ->join('negocios', 'citas.id_negocio', '=', 'negocios.id')
                ->where('negocios.id_usuario', $idUsuario)
                ->where('citas.fecha', $hoy)
                ->count();

            // Declaramos variables por defecto por si no tiene plan
            $planAdquirido = null;
            $prioridadesTicket = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();

            // Solo hacemos los cálculos matemáticos si SÍ tiene un plan
            if($usuarioLoggeado->id_plan != null){
                $globalFuncion = new \App\Clases\GlobalFuncion();
                $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado->id_plan);

                // Lógica de Descarga de Excel
                $hoySemana = \Carbon\Carbon::now()->dayOfWeek;
                if (isset($planAdquirido->dias_respaldo_calendario) && $planAdquirido->dias_respaldo_calendario !== null) {
                    $cadenaLimpia = str_replace(' ', '', $planAdquirido->dias_respaldo_calendario);
                    $diasPermitidos = explode(',', $cadenaLimpia);
                    $planAdquirido->puede_descargar_hoy = in_array((string)$hoySemana, $diasPermitidos);
                } else {
                    $planAdquirido->puede_descargar_hoy = false;
                }

                // Lógica de Créditos de WhatsApp
                $mesActual = \Carbon\Carbon::now()->month;
                $anioActual = \Carbon\Carbon::now()->year;

                $creditosGastados = DB::table('creditos_whatsapp_negocio as cw')
                    ->join('negocios as n', 'cw.id_negocio', '=', 'n.id')
                    ->where('n.id_usuario', $idUsuario)
                    ->whereMonth('cw.created_at', $mesActual)
                    ->whereYear('cw.created_at', $anioActual)
                    ->count();

                $limiteCreditos = (int)($planAdquirido->whatsapp_creditos_iniciales ?? 0);
                $planAdquirido->creditos_restantes = max(0, $limiteCreditos - $creditosGastados);
            }

            return response()->json([
                'valid' => true,
                'planAdquirido' => $planAdquirido,
                'usuarioLoggeado' => $usuarioLoggeado,
                'cantidadNegocios' => $cantidadNegocios,
                'prioridadesTicket' => $prioridadesTicket
            ]);
        }

        return response()->json(['valid' => false, 'planAdquirido' => null]);
    }

    public function misNegocios(Request $request){
        $idUsuario = Auth::id();

        $usuarioLoggeado = DB::table('users as u')
            ->leftJoin('plan_usuarios as pu', 'u.id', '=', 'pu.id_usuario')
            ->leftJoin('roles_usuarios as ru', 'u.id', '=', 'ru.id_usuario')
            ->select('pu.id_plan', 'u.email', 'u.name', 'ru.id_rol')
            ->where('u.id', $idUsuario)
            ->first();

        // Si por alguna razón el usuario no tiene rol registrado, forzamos que sea Dueño (2)
        if ($usuarioLoggeado && $usuarioLoggeado->id_rol == null) {
            $usuarioLoggeado->id_rol = 2;
        }

        $planAdquirido = null;
        if($usuarioLoggeado && $usuarioLoggeado->id_plan) {
            $globalFuncion = new GlobalFuncion();
            $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado->id_plan);

            // --- REPETIMOS LA LÓGICA AQUÍ POR SI EL FRONTEND USA ESTA RUTA ---
            $hoy = Carbon::now()->dayOfWeek;
            if (isset($planAdquirido->dias_respaldo_calendario) && $planAdquirido->dias_respaldo_calendario !== null) {
                $diasPermitidos = explode(',', $planAdquirido->dias_respaldo_calendario);
                $planAdquirido->puede_descargar_hoy = in_array((string)$hoy, $diasPermitidos);
            } else {
                $planAdquirido->puede_descargar_hoy = false;
            }
            // ------------------------------------------------------
        }

        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        foreach ($negocios as $negocio) {
            $horariosDB = DB::table('horarios_negocios')->where('id_negocio', $negocio->id)->get();
            $horariosArray = [];
            foreach ($horariosDB as $horario) {
                $horariosArray[] = $horario->hora_inicio . ' - ' . $horario->hora_fin;
            }
            $negocio->horarios = $horariosArray;

            $telefonosDB = DB::table('numeros_telefonos_negocio')->where('id_negocio', $negocio->id)->get();
            $telefonosArray = [];
            foreach ($telefonosDB as $tel) {
                $telefonosArray[] = [
                    'id_tipo' => $tel->id_tipo_numero_telefono,
                    'numero' => $tel->numero_telefono
                ];
            }
            $negocio->telefonos = $telefonosArray;
        }

        return response()->json([
            'valid' => true,
            'usuarioLoggeado' => $usuarioLoggeado,
            'planAdquirido' => $planAdquirido,
            'negocios' => $negocios
        ]);
    }

    public function actualizarNegocio(Request $request, $id){
        try {
            $negocioMio = DB::table('negocios')
                ->where('id', $id)
                ->where('id_usuario', Auth::id())
                ->first();

            if(!$negocioMio){
                return response()->json([
                    'valid' => false,
                    'message' => 'No tienes permisos para editar este negocio'
                ]);
            }

            // Preparar datos base para actualizar
            $updateData = [
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'updated_at' => Carbon::now()
            ];

            // 1. PROCESAMOS EL ARCHIVO FÍSICO PRIMERO (Fuera de la transacción)
            if ($request->hasFile('logo')) {
                $carpetaDestino = base_path('../uploads/documentos/imagenes/');

                if ($negocioMio->logo) {
                    $rutaLogoViejo = base_path('../' . $negocioMio->logo);
                    if (File::exists($rutaLogoViejo)) {
                        File::delete($rutaLogoViejo);
                    }
                }

                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

                $archivo = $request->file('logo');
                $nombreArchivo = 'Foto-logo-' . $id . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
                $archivo->move($carpetaDestino, $nombreArchivo);

                $updateData['logo'] = 'uploads/documentos/imagenes/' . $nombreArchivo;
            }

            // 2. ABRIMOS LA TRANSACCIÓN PARA TODOS LOS MOVIMIENTOS SQL
            DB::transaction(function () use ($request, $id, $updateData) {

                // Actualizamos datos principales
                DB::table('negocios')
                    ->where('id', $id)
                    ->update($updateData);

                // Reemplazamos teléfonos
                DB::table('numeros_telefonos_negocio')->where('id_negocio', $id)->delete();
                if ($request->has('telefonos') && is_array($request->telefonos)) {
                    $telefonosInsert = [];
                    foreach($request->telefonos as $tel){
                        if (!empty($tel['numero'])) {
                            $telefonosInsert[] = [
                                'id_negocio' => $id,
                                'id_tipo_numero_telefono' => $tel['id_tipo'],
                                'numero_telefono' => $tel['numero'],
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];
                        }
                    }
                    if (count($telefonosInsert) > 0) {
                        DB::table('numeros_telefonos_negocio')->insert($telefonosInsert);
                    }
                }

                // Reemplazamos horarios
                DB::table('horarios_negocios')->where('id_negocio', $id)->delete();
                if ($request->has('horarios') && is_array($request->horarios)) {
                    foreach($request->horarios as $horario){
                        $horas = explode(' - ', $horario);
                        if (count($horas) == 2) {
                            DB::table('horarios_negocios')->insert([
                                'id_negocio' => $id,
                                'hora_inicio' => trim($horas[0]),
                                'hora_fin' => trim($horas[1]),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ]);
                        }
                    }
                }

                // Registramos el log
                $this->guardarLog('negocios', 'editar', $request->nombre, $request->all());

            }); // FIN DE LA TRANSACCIÓN

            return response()->json([
                'valid' => true,
                'message' => '¡Negocio actualizado correctamente!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }
}
