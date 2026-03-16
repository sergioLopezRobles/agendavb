<?php

namespace App\clases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GlobalFuncion
{
    public function obtenerPlanCompleto($idPlan)
    {
        $plan = DB::table('planes')->where('id', $idPlan)->first();

        if (!$plan) {
            return null;
        }

        $caracteristicas = DB::table('caracteristicasplanes')
            ->where('id_plan', $idPlan)
            ->get();

        foreach ($caracteristicas as $c) {
            $plan->{$c->titulo} = $c->valor; //
        }

        return $plan;
    }

    public function obtenerAtributoPlan($idPlan, $atributo)
    {
        $valor = DB::table('caracteristicasplanes')
            ->where('id_plan', $idPlan)
            ->where('titulo', $atributo)
            ->value('valor');

        return $valor;
    }

    public static function generarIdRandom($length)
    {
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($caracteres);
        $randomId = '';
        for ($i = 0; $i < $length; $i++) {
            $randomId .= $caracteres[rAND(0, $charactersLength - 1)];
        }
        return $randomId;
    }

}
