<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class NegocioAdminController extends Controller
{
    public function index()
    {
        $negocios = DB::table('negocios as n')
            ->join('users as u', 'n.id_usuario', '=', 'u.id')
            ->leftJoin('planes as p', 'n.id_plan', '=', 'p.id')
            ->select(
                'n.id',
                'n.nombre as negocio_nombre',
                'n.email as negocio_email',
                'n.logo',
                'n.direccion',
                'n.whatsapp_creditos',
                'u.name as dueno_nombre',
                'u.telefono as dueno_telefono',
                'p.nombre as plan_nombre',
                DB::raw("DATE_FORMAT(n.created_at, '%d/%m/%Y') as fecha_creacion")
            )
            ->orderBy('n.id', 'desc')
            ->get();

        return response()->json([
            'valid' => true,
            'negocios' => $negocios
        ]);
    }

    public function obtenerCitasNegocio($id)
    {
        $citas = DB::table('citas as c')
            ->join('servicios as s', 'c.id_servicio', '=', 's.id')
            ->select(
                'c.id',
                'c.cliente_nombre',
                'c.cliente_telefono',
                'c.fecha',
                'c.hora',
                's.nombre as servicio_nombre'
            )
            ->where('c.id_negocio', $id)
            ->get();

        return response()->json([
            'valid' => true,
            'citas' => $citas
        ]);
    }

    public function obtenerServiciosNegocio($id)
    {
        $servicios = DB::table('servicios')
            ->where('id_negocio', $id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'valid' => true,
            'servicios' => $servicios
        ]);
    }

    public function obtenerEstadisticasNegocio($id)
    {
        $mesActual = \Carbon\Carbon::now()->month;
        $anioActual = \Carbon\Carbon::now()->year;

        // 1. Conteo dinámico haciendo JOIN a tu tabla 'estados_cita'
        $statsEstados = DB::table('citas as c')
            ->join('estados_cita as e', 'c.id_estado', '=', 'e.id')
            ->select('e.titulo as estado', DB::raw('count(c.id) as total'))
            ->where('c.id_negocio', $id)
            ->whereMonth('c.fecha', $mesActual)
            ->whereYear('c.fecha', $anioActual)
            ->groupBy('e.titulo')
            ->get();

        // 2. Top 3 Servicios más vendidos
        $topServicios = DB::table('citas as c')
            ->join('servicios as s', 'c.id_servicio', '=', 's.id')
            ->select('s.nombre', DB::raw('count(*) as total'))
            ->where('c.id_negocio', $id)
            ->groupBy('s.nombre')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get();

        // 3. Ingresos estimados
        $ingresosEstimados = DB::table('citas')
            ->where('id_negocio', $id)
            ->where('id_estado', '3')
            ->whereMonth('fecha', $mesActual)
            ->sum(DB::raw('CAST(total AS DECIMAL(10,2))'));

        return response()->json([
            'valid' => true,
            'stats' => $statsEstados,
            'topServicios' => $topServicios,
            'ingresos' => $ingresosEstimados,
            'totalMes' => $statsEstados->sum('total')
        ]);
    }
}
