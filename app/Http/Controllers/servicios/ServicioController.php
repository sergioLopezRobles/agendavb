<?php

namespace App\Http\Controllers\servicios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ServicioController extends Controller
{
    // TRAER SERVICIOS DE UN NEGOCIO ESPECIFICO
    public function obtenerServicios($idNegocio)
    {
        try {
            $negocio = DB::table('negocios')
                ->where('id', $idNegocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json([
                    'valid' => false,
                    'message' => 'No tienes acceso a este negocio'
                ]);
            }

            $servicios = DB::table('servicios')
                ->where('id_negocio', $idNegocio)
                ->orderBy('id', 'desc')
                ->get();

            $minutosPermitidos = DB::table('minutos_servicios_planes')
                ->where('id_plan', $negocio->id_plan)
                ->pluck('minutos');

            // OBTENER LÍMITE DE SERVICIOS DEL PLAN
            $limiteServiciosRow = DB::table('caracteristicasplanes')
                ->where('id_plan', $negocio->id_plan)
                ->where('titulo', 'limite_servicios_negocios')
                ->first();

            // null = ilimitado (Plan Avanzado)
            $limiteServicios = $limiteServiciosRow ? $limiteServiciosRow->valor : null;

            return response()->json([
                'valid'              => true,
                'servicios'          => $servicios,
                'minutos_permitidos' => $minutosPermitidos,
                'limite_servicios'   => $limiteServicios,   // null = ilimitado
                'total_servicios'    => $servicios->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

// CREAR UN NUEVO SERVICIO
    public function store(Request $request)
    {
        try {
            $negocio = DB::table('negocios')
                ->where('id', $request->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'No tienes permisos para agregar servicios aquí'
                ]);
            }

            // VALIDAR LÍMITE DE SERVICIOS POR PLAN
            $limiteRow = DB::table('caracteristicasplanes')
                ->where('id_plan', $negocio->id_plan)
                ->where('titulo', 'limite_servicios_negocios')
                ->first();

            $limite = $limiteRow ? $limiteRow->valor : null;

            if ($limite !== null) {
                $totalActual = DB::table('servicios')
                    ->where('id_negocio', $request->id_negocio)
                    ->count();

                if ($totalActual >= (int) $limite) {
                    return response()->json([
                        'valid'   => false,
                        'message' => 'Tu plan permite un máximo de ' . $limite . ' servicios por negocio. Mejora tu plan para agregar más.'
                    ]);
                }
            }

            DB::table('servicios')->insert([
                'id_negocio'       => $request->id_negocio,
                'nombre'           => $request->nombre,
                'precio'           => $request->precio,
                'duracion_minutos' => $request->duracion_minutos,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ]);

            return response()->json([
                'valid'   => true,
                'message' => 'Servicio agregado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    // ACTUALIZAR UN SERVICIO
    public function update(Request $request, $id)
    {
        try {
            // PRIMERO OBTENEMOS EL SERVICIO PARA SABER A QUE NEGOCIO PERTENECE
            $servicio = DB::table('servicios')->where('id', $id)->first();
            if (!$servicio) {
                return response()->json(['valid' => false, 'message' => 'Servicio no encontrado']);
            }

            // VERIFICAMOS QUE EL NEGOCIO SEA DEL USUARIO
            $negocio = DB::table('negocios')
                ->where('id', $servicio->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'No tienes permisos']);
            }

            // ACTUALIZAR EL REGISTRO
            DB::table('servicios')
                ->where('id', $id)
                ->update([
                    'nombre' => $request->nombre,
                    'precio' => $request->precio,
                    'duracion_minutos' => $request->duracion_minutos,
                    'updated_at' => Carbon::now()
                ]);

            return response()->json([
                'valid' => true,
                'message' => 'Servicio actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    // ELIMINAR UN SERVICIO
    public function destroy($id)
    {
        try {
            // BUSCAR EL SERVICIO
            $servicio = DB::table('servicios')->where('id', $id)->first();
            if (!$servicio) {
                return response()->json(['valid' => false, 'message' => 'Servicio no encontrado']);
            }

            // VERIFICAR PROPIEDAD DEL NEGOCIO
            $negocio = DB::table('negocios')
                ->where('id', $servicio->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'No tienes permisos']);
            }

            // BORRAR REGISTRO
            DB::table('servicios')->where('id', $id)->delete();

            return response()->json([
                'valid' => true,
                'message' => 'Servicio eliminado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
