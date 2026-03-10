<?php

namespace App\Http\Controllers\plan;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function verplanes(){

        $rows = DB::select('
        SELECT cp.id_plan, cp.titulo, cp.valor, p.nombre
        FROM planes p
        INNER JOIN caracteristicasplanes cp ON cp.id_plan = p.id
        ORDER BY p.created_at DESC
    ');

        $planes = [];

        foreach ($rows as $row) {

            if(!isset($planes[$row->id_plan])){
                $planes[$row->id_plan] = [
                    'id' => $row->id_plan,
                    'nombre' => $row->nombre,
                    'caracteristicas' => []
                ];
            }

            $planes[$row->id_plan]['caracteristicas'][$row->titulo] = $row->valor;
        }

        return response()->json(array_values($planes));
    }
}
