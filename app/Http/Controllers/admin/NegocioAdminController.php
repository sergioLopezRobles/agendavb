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
}
