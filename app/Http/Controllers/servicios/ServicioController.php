<?php

namespace App\Http\Controllers\servicios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ServicioController extends Controller
{
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

            $limiteServiciosRow = DB::table('caracteristicasplanes')
                ->where('id_plan', $negocio->id_plan)
                ->where('titulo', 'limite_servicios_negocios')
                ->first();
            $limiteServicios = $limiteServiciosRow ? $limiteServiciosRow->valor : null;

            // OBTENER PORCENTAJE DE ANTICIPO DEL PLAN
            $anticipoRow = DB::table('caracteristicasplanes')
                ->where('id_plan', $negocio->id_plan)
                ->where('titulo', 'anticipo_forzoso')
                ->first();
            $porcentajeAnticipo = $anticipoRow ? $anticipoRow->valor : 0;

            return response()->json([
                'valid'               => true,
                'servicios'           => $servicios,
                'minutos_permitidos'  => $minutosPermitidos,
                'limite_servicios'    => $limiteServicios,
                'porcentaje_anticipo' => $porcentajeAnticipo,
                'total_servicios'     => $servicios->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

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

            $limiteRow = DB::table('caracteristicasplanes')
                ->where('id_plan', $negocio->id_plan)
                ->where('titulo', 'limite_servicios_negocios')
                ->first();
            $limite = $limiteRow ? $limiteRow->valor : null;

            if ($limite !== null) {
                $totalActual = DB::table('servicios')->where('id_negocio', $request->id_negocio)->count();
                if ($totalActual >= (int) $limite) {
                    return response()->json([
                        'valid'   => false,
                        'message' => 'Tu plan permite un máximo de ' . $limite . ' servicios. Mejora tu plan para agregar más.'
                    ]);
                }
            }

            // GUARDAMOS CON EL ANTICIPO
            DB::table('servicios')->insert([
                'id_negocio'       => $request->id_negocio,
                'nombre'           => $request->nombre,
                'precio'           => $request->precio,
                'anticipo'         => $request->anticipo, // -> NUEVO CAMPO
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

    public function update(Request $request, $id)
    {
        try {
            $servicio = DB::table('servicios')->where('id', $id)->first();
            if (!$servicio) {
                return response()->json(['valid' => false, 'message' => 'Servicio no encontrado']);
            }

            $negocio = DB::table('negocios')
                ->where('id', $servicio->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'No tienes permisos']);
            }

            // ACTUALIZAMOS CON EL ANTICIPO
            DB::table('servicios')
                ->where('id', $id)
                ->update([
                    'nombre'           => $request->nombre,
                    'precio'           => $request->precio,
                    'anticipo'         => $request->anticipo, // -> NUEVO CAMPO
                    'duracion_minutos' => $request->duracion_minutos,
                    'updated_at'       => Carbon::now()
                ]);

            return response()->json([
                'valid'   => true,
                'message' => 'Servicio actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $servicio = DB::table('servicios')->where('id', $id)->first();
            if (!$servicio) {
                return response()->json(['valid' => false, 'message' => 'Servicio no encontrado']);
            }

            $negocio = DB::table('negocios')
                ->where('id', $servicio->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'No tienes permisos']);
            }

            DB::table('servicios')->where('id', $id)->delete();

            return response()->json([
                'valid'   => true,
                'message' => 'Servicio eliminado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
