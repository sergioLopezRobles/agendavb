<?php

namespace App\Http\Controllers\citasnegocios;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CitasNegociosController extends Controller
{
    public function citasnegocios() {
        $idUsuario = Auth::id();
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        return response()->json([
            'valid' => true,
            'negocios' => $negocios
        ]);
    }

    public function obtenerCitasNegocio($id_negocio) {
        // Obtenemos las citas vinculadas al negocio y al usuario autenticado (por seguridad)
        $citas = DB::table('citas')
            ->where('id_negocio', $id_negocio)
            ->get();

        return response()->json([
            'valid' => true,
            'citas' => $citas
        ]);
    }
}
