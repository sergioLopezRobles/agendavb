<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Clases\GlobalFuncion;

class DashboardController extends Controller
{
    public function index(){
        $idUsuario = Auth::id();

        Log::info('entro Dashboard ' . $idUsuario);

        $usuarioLoggeado = DB::select("SELECT id_plan, email, name FROM users WHERE id = " . $idUsuario);

        $cantidadNegocios = DB::table('negocios')->where('id_usuario', $idUsuario)->count();
        if($usuarioLoggeado != null){
            if($usuarioLoggeado[0]->id_plan != null){
                $globalFuncion = new GlobalFuncion();
                $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado[0]->id_plan);
                $prioridadesTicket = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();

                return response()->json([
                    'valid' => true,
                    'planAdquirido' => $planAdquirido,
                    'usuarioLoggeado' => $usuarioLoggeado[0],
                    'cantidadNegocios' => $cantidadNegocios,
                    'prioridadesTicket' => $prioridadesTicket
                ]);
            }else{
                return response()->json([
                    'valid' => true,
                    'planAdquirido' => null,
                    'usuarioLoggeado' => $usuarioLoggeado[0],
                    'cantidadNegocios' => $cantidadNegocios
                ]);
            }
        }

        return response()->json([
            'valid' => false,
            'planAdquirido' => null,
        ]);
    }

    public function registrarPlanNegocio(Request $request){
        try {
            $planNegocio = DB::table('planes')->where('id', $request->plan)->first();

            if($planNegocio != null){
                $globalFuncion = new GlobalFuncion();
                $caracteristicasPlan = $globalFuncion->obtenerPlanCompleto($planNegocio->id);

                $maxPermitido = $caracteristicasPlan->maximonegocios ?? 1;
                $cantidadActual = DB::table('negocios')->where('id_usuario', Auth::id())->count();

                if ($cantidadActual >= $maxPermitido) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Límite alcanzado. Tu plan permite un máximo de ' . $maxPermitido . ' negocio(s).'
                    ]);
                }

                $slugBase = $request->slug ? Str::slug($request->slug) : Str::slug($request->nombre);
                $slug = 'www.agendavb/' . $slugBase . '.com';

                if($planNegocio->id != 3){
                    $slug = 'www.agendavb/' . Str::uuid() . '.com';
                }

                $existeSlug = DB::table('negocios')->where('slug', $slug)->exists();
                if ($existeSlug && $planNegocio->id == 3) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Esta URL de negocio ya está ocupada. Por favor elige otra.'
                    ]);
                }

                // 1. INSERCIÓN DEL NEGOCIO (SIN TELÉFONO)
                $idNegocio = DB::table('negocios')->insertGetId([
                    'id_usuario' => $request->user()->id,
                    'id_plan' => $request->plan,
                    'nombre' => $request->nombre,
                    'slug' => $slug,
                    'email' => $request->email,
                    'whatsapp_creditos' => $caracteristicasPlan->whatsapp_creditos_iniciales ?? 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                // 2. INSERCIÓN DE MÚLTIPLES TELÉFONOS
                if ($request->has('telefonos') && is_array($request->telefonos)) {
                    $telefonosInsert = [];
                    foreach ($request->telefonos as $tel) {
                        if (!empty($tel['numero'])) {
                            $telefonosInsert[] = [
                                'id_negocio' => $idNegocio,
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

                // 3. INSERCIÓN DE HORARIOS
                foreach($request->horarios as $horario){
                    $horas = explode(' - ', $horario);
                    DB::table('horarios_negocios')->insert([
                        'id_negocio' => $idNegocio,
                        'hora_inicio' => $horas[0],
                        'hora_fin' => $horas[1],
                    ]);
                }

                DB::table('users')->where('id', Auth::id())->update([
                    'id_plan' => $request->plan
                ]);

                return response()->json([
                    'valid' => true,
                    'message' => '¡Negocio registrado exitosamente!'
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => 'El plan no existe'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }

    public function misNegocios(Request $request){
        $idUsuario = Auth::id();

        $usuarioLoggeado = DB::table('users')->select('name', 'email', 'id_plan')->where('id', $idUsuario)->first();
        $planAdquirido = DB::table('planes')->where('id', $usuarioLoggeado->id_plan)->first();
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        foreach ($negocios as $negocio) {
            // Adjuntar Horarios
            $horariosDB = DB::table('horarios_negocios')->where('id_negocio', $negocio->id)->get();
            $horariosArray = [];
            foreach ($horariosDB as $horario) {
                $horariosArray[] = $horario->hora_inicio . ' - ' . $horario->hora_fin;
            }
            $negocio->horarios = $horariosArray;

            // Adjuntar Teléfonos
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

            // Actualizamos datos básicos (ya sin teléfono)
            DB::table('negocios')
                ->where('id', $id)
                ->update([
                    'nombre' => $request->nombre,
                    'updated_at' => Carbon::now()
                ]);

            // ACTUALIZAMOS TELÉFONOS
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

            // ACTUALIZAMOS HORARIOS
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
