<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait RegistraMovimientos
{
    public function guardarLog($tabla, $accion, $identificador, $detalles = [])
    {
        if (Auth::check()) {
            $tipos_mensaje = ['crear' => 1, 'editar' => 2, 'eliminar' => 3];
            $tipo_mensaje = $tipos_mensaje[$accion] ?? 0;

            $nombresAmigables = [
                'negocios'  => 'Negocio',
                'servicios' => 'Servicio',
                'citas'     => 'Cita',
                'tickets'   => 'Ticket',
                'planes'    => 'Plan',
                'usuarios'  => 'Usuario'
            ];
            $nombreItem = $nombresAmigables[$tabla] ?? $tabla;

            // 2. Magia: Si modificamos un usuario y nos mandan su ID (número), buscamos su nombre
            if ($tabla === 'usuarios' && is_numeric($identificador)) {
                $usuarioAfectado = DB::table('users')->where('id', $identificador)->first();
                if ($usuarioAfectado) {
                    $identificador = $usuarioAfectado->name;
                }
            }

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
