<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait RegistraMovimientos
{
    /**
     * Guarda el movimiento del usuario.
     * * @param string $tabla El nombre de la tabla (ej. 'servicios', 'negocios')
     * @param string $accion 'crear', 'editar', 'eliminar'
     * @param string $identificador Nombre de lo que se afectó (ej. 'Corte de Cabello')
     * @param array $detalles Arreglo con los datos extras
     */
    public function guardarLog($tabla, $accion, $identificador, $detalles = [])
    {
        if (Auth::check()) {
            $tipos_mensaje = ['crear' => 1, 'editar' => 2, 'eliminar' => 3];
            $tipo_mensaje = $tipos_mensaje[$accion] ?? 0;

            // Diccionario para que el mensaje no diga "servicios", sino "Servicio"
            $nombresAmigables = [
                'negocios'  => 'Negocio',
                'servicios' => 'Servicio',
                'citas'     => 'Cita',
                'tickets'   => 'Ticket',
                'planes'    => 'Plan'
            ];
            $nombreItem = $nombresAmigables[$tabla] ?? $tabla;

            $textos = [
                'crear'    => "Creo un nuevo $nombreItem: $identificador",
                'editar'   => "Actualizo el $nombreItem: $identificador",
                'eliminar' => "Elimino el $nombreItem: $identificador"
            ];

            $cambios = [
                'mensaje'  => $textos[$accion] ?? "Accion no definida",
                'tabla'    => $tabla,
                'detalles' => $detalles
            ];

            DB::table('movimientos_usuarios')->insert([
                'id_usuario'   => Auth::id(),
                'cambios'      => json_encode($cambios),
                'tipo_mensaje' => $tipo_mensaje,
                'created_at'   => now(),
                'updated_at'   => now()
            ]);
        }
    }
}
