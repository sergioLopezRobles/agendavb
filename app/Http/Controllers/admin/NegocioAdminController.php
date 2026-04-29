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
}
