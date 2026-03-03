<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(){
        $idUsuario = Auth::id();

        Log::info('entro Dashboard ' . $idUsuario);

        // 1. AÑADIMOS 'name' a la consulta para que Vue lo pueda mostrar
        $usuarioLoggeado = DB::select("SELECT id_plan, email, name FROM users WHERE id = " . $idUsuario);

        if($usuarioLoggeado != null){
            if($usuarioLoggeado[0]->id_plan != null){

                // Buscamos los datos del plan (Créditos, Nombre, etc)
                $planAdquirido = DB::select("SELECT * FROM planes WHERE id = " . $usuarioLoggeado[0]->id_plan);

                return response()->json([
                    'valid' => true,
                    // 2. AÑADIMOS [0] para enviar el objeto limpio a Vue, no un arreglo
                    'planAdquirido' => $planAdquirido[0],
                    'usuarioLoggeado' => $usuarioLoggeado[0],
                ]);
            }else{
                return response()->json([
                    'valid' => true,
                    'planAdquirido' => null,
                    'usuarioLoggeado' => $usuarioLoggeado[0], // También aquí
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
            $planNegocio = DB::select("SELECT whatsapp_creditos_iniciales FROM planes WHERE id = " . $request->plan);
            $slug = Str::slug($request->nombre);

            DB::table('negocios')->insert([
                'id_usuario' => $request->user()->id,
                'id_plan' => $request->plan,
                'nombre' => $request->nombre,
                'slug' => $slug,
                'email' => $request->email,
                'telefono' => $request->telefono ?? '0000000000',
                'whatsapp_creditos' => $planNegocio[0]->whatsapp_creditos_iniciales,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::table('users')->where('id',Auth::id())->update([
                'id_plan' => $request->plan
            ]);

            return response()->json([
                'valid' => true,
                'message' => '¡Negocio registrado exitosamente!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }
}
