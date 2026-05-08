<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class NegocioAdminController extends Controller
{
    public function index()
    {
        // 1. Obtenemos a los usuarios (Admins y Dueños) para el directorio
        $usuarios = DB::table('users as u')
            ->join('roles_usuarios as ru', 'u.id', '=', 'ru.id_usuario')
            ->join('roles as r', 'ru.id_rol', '=', 'r.id')
            ->select(
                'u.id',
                'u.name as dueno_nombre',
                'u.email as dueno_email',
                'u.telefono as dueno_telefono',
                'u.avatar as dueno_avatar',
                'r.titulo as rol_nombre',
                'ru.id_rol',
                DB::raw("DATE_FORMAT(u.created_at, '%d/%m/%Y') as fecha_registro")
            )
            ->whereIn('ru.id_rol', [1, 2]) //Admins (1) y Dueños (2)
            ->orderBy('u.id', 'desc')
            ->get();

        foreach ($usuarios as $u) {
            // Buscamos el plan
            $plan = DB::table('plan_usuarios as pu')
                ->join('planes as p', 'pu.id_plan', '=', 'p.id')
                ->where('pu.id_usuario', $u->id)
                ->select('p.nombre')
                ->first();

            // Si no hay plan, sale SIN PLAN
            $u->plan_nombre = $plan ? $plan->nombre : 'SIN PLAN';

            // Buscamos sus negocios
            $u->negocios = DB::table('negocios')
                ->select('id', 'nombre', 'email', 'logo', 'direccion', 'whatsapp_creditos')
                ->where('id_usuario', $u->id)
                ->orderBy('id', 'desc')
                ->get();
        }

        return response()->json([
            'valid' => true,
            'duenos' => $usuarios
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

        // 1. Conteo dinámico haciendo JOIN a tabla 'estados_cita'
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
