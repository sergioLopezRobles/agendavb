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

                return response()->json([
                    'valid' => true,
                    // 2. se anade [0] para enviar el objeto limpio a Vue, no un arreglo
                    'planAdquirido' => $planAdquirido,
                    'usuarioLoggeado' => $usuarioLoggeado[0],
                    'cantidadNegocios' => $cantidadNegocios // 👇 LO ENVIAMOS A VUE
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

                $maxPermitido = $caracteristicasPlan['maximonegocios'] ?? 1;

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
                DB::table('negocios')->insert([
                    'id_usuario' => $request->user()->id,
                    'id_plan' => $request->plan,
                    'nombre' => $request->nombre,
                    'slug' => $slug, // Se guarda nuestro nuevo slug validado
                    'email' => $request->email,
                    'telefono' => $request->telefono ?? '0000000000',
                    'whatsapp_creditos' => $caracteristicasPlan['whatsapp_creditos_iniciales'] ?? 0,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

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

        // 1. Obtenemos datos del usuario y su plan
        $usuarioLoggeado = DB::table('users')->select('name', 'email', 'id_plan')->where('id', $idUsuario)->first();
        $planAdquirido = DB::table('planes')->where('id', $usuarioLoggeado->id_plan)->first();

        // 2. Traemos TODOS los negocios que le pertenecen a este usuario
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

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
            DB::table('negocios')
                ->where('id', $id)
                ->where('id_usuario', Auth::id())
                ->update([
                    'nombre' => $request->nombre,
                    'telefono' => $request->telefono,
                    'updated_at' => Carbon::now()
                ]);

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
