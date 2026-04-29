<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function index()
    {
        $logs = DB::table('movimientos_usuarios as m')
            ->join('users as u', 'm.id_usuario', '=', 'u.id')
            ->select(
                'm.id',
                'u.name as usuario_nombre',
                'u.email',
                'm.cambios',
                'm.tipo_mensaje',
                DB::raw("DATE_FORMAT(m.created_at, '%d/%m/%Y %H:%i') as fecha")
            )
            ->orderBy('m.created_at', 'desc')
            ->get()
            ->map(function ($log) {
                $log->cambios = json_decode($log->cambios);
                return $log;
            });

        return response()->json([
            'valid' => true,
            'logs' => $logs
        ]);
    }
}
