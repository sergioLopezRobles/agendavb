<?php

namespace App\Http\Controllers\citasnegocios;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
        $estados = DB::table('estados_cita')->get();

        return response()->json([
            'valid' => true,
            'citas' => $citas,
            'estados' => $estados
        ]);
    }

    public function actualizarEstadoCita(Request $request) {
        // Validación básica para evitar errores
        if (!$request->id_cita || !$request->id_estado) {
            return response()->json(['valid' => false, 'message' => 'Datos incompletos']);
        }

        DB::table('citas')
            ->where('id', $request->id_cita)
            ->update([
                'id_estado' => $request->id_estado
            ]);

        return response()->json(['valid' => true, 'message' => 'Estado actualizado']);
    }
}
