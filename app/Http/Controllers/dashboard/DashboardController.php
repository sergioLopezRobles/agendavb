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

        // 1. se anade 'name' a la consulta para que Vue lo pueda mostrar
        $usuarioLoggeado = DB::select("SELECT id_plan, email, name FROM users WHERE id = " . $idUsuario);

        // Contamos los negocios del usuario
        $cantidadNegocios = DB::table('negocios')->where('id_usuario', $idUsuario)->count();
        if($usuarioLoggeado != null){
            if($usuarioLoggeado[0]->id_plan != null){
                // Buscamos los datos del plan (Créditos, Nombre, etc)
                $globalFuncion = new GlobalFuncion();
                $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado[0]->id_plan);

                $prioridadesTicket = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();

                return response()->json([
                    'valid' => true,
                    // 2. se anade [0] para enviar el objeto limpio a Vue, no un arreglo
                    'planAdquirido' => $planAdquirido,
                    'usuarioLoggeado' => $usuarioLoggeado[0],
                    'cantidadNegocios' => $cantidadNegocios, // 👇 LO ENVIAMOS A VUE
                    'prioridadesTicket' => $prioridadesTicket
                ]);
            }else{
                return response()->json([
                    'valid' => true,
                    'planAdquirido' => null,
                    'usuarioLoggeado' => $usuarioLoggeado[0], // También aquí
                    'cantidadNegocios' => $cantidadNegocios // 👇 LO ENVIAMOS A VUE
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
                //TRAER CARACTERISTICAS DEL PLAN
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

                // 1. Armamos el slug (usamos el personalizado, y si está vacío usamos el nombre)
                $slugBase = $request->slug ? Str::slug($request->slug) : Str::slug($request->nombre);
                $slug = 'www.agendavb/' . $slugBase . '.com';

                // Si es Básico o Medio, sobreescribimos con un UUID aleatorio
                if($planNegocio->id != 3){
                    $slug = 'www.agendavb/' . Str::uuid() . '.com';
                }

                // 2. CANDADO DE URL: Revisamos si esa URL ya está ocupada
                $existeSlug = DB::table('negocios')->where('slug', $slug)->exists();
                if ($existeSlug && $planNegocio->id == 3) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Esta URL de negocio ya está ocupada. Por favor elige otra.'
                    ]);
                }

                // 3. Inserción normal
                $idNegocio = DB::table('negocios')->insertGetId([
                    'id_usuario' => $request->user()->id,
                    'id_plan' => $request->plan,
                    'nombre' => $request->nombre,
                    'slug' => $slug, // Se guarda nuestro nuevo slug validado
                    'email' => $request->email,
                    'telefono' => $request->telefono ?? '0000000000',
                    'whatsapp_creditos' => $caracteristicasPlan->whatsapp_creditos_iniciales?? 0,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

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

    //NUEVAS FUNCIONES PARA EL MÓDULO DE NEGOCIOS
    public function misNegocios(Request $request){
        $idUsuario = Auth::id();

        //Obtenemos datos del usuario y su plan
        $usuarioLoggeado = DB::table('users')->select('name', 'email', 'id_plan')->where('id', $idUsuario)->first();
        $planAdquirido = DB::table('planes')->where('id', $usuarioLoggeado->id_plan)->first();

        //Traemos TODOS los negocios que le pertenecen a este usuario
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        //inserta los horarios a cada negocio antes de enviarlos a Vue
        foreach ($negocios as $negocio) {
            $horariosDB = DB::table('horarios_negocios')->where('id_negocio', $negocio->id)->get();
            $horariosArray = [];

            foreach ($horariosDB as $horario) {
                // Arma el string exacto que Vue necesita ("08:00 - 09:00")
                $horariosArray[] = $horario->hora_inicio . ' - ' . $horario->hora_fin;
            }

            $negocio->horarios = $horariosArray;
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
            // Seguridad: Aseguramos que el usuario solo edite SU propio negocio
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

            // Actualizamos los datos básicos
            DB::table('negocios')
                ->where('id', $id)
                ->update([
                    'nombre' => $request->nombre,
                    'telefono' => $request->telefono,
                    'updated_at' => Carbon::now()
                ]);

            //ACTUALIZAMOS LOS HORARIOS
            // 1. Borramos todos los horarios viejos de este negocio
            DB::table('horarios_negocios')->where('id_negocio', $id)->delete();

            // 2. Insertamos los nuevos que mandó Vue
            if ($request->has('horarios') && is_array($request->horarios)) {
                foreach($request->horarios as $horario){
                    $horas = explode(' - ', $horario); // Partimos "08:00 - 09:00" en dos

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
