<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Traits\RegistraMovimientos;

class UsuarioAdminController extends Controller
{
    use RegistraMovimientos;

    // Obtener todos los usuarios
    public function index()
    {
        $usuarios = DB::table('users as u')
            ->leftJoin('roles_usuarios as ru', 'u.id', '=', 'ru.id_usuario')
            ->leftJoin('roles as r', 'ru.id_rol', '=', 'r.id')
            ->leftJoin('plan_usuarios as pu', 'u.id', '=', 'pu.id_usuario')
            ->leftJoin('planes as p', 'pu.id_plan', '=', 'p.id')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.telefono',
                'u.estatus',
                'ru.id_rol',
                'r.titulo as rol_nombre',
                'p.nombre as plan_nombre', // <-- Tomamos el nombre desde la tabla planes
                DB::raw("DATE_FORMAT(u.created_at, '%d/%m/%Y') as fecha_registro")
            )
            ->orderBy('u.id', 'desc')
            ->get();

        $roles = DB::table('roles')->get();

        return response()->json([
            'valid' => true,
            'usuarios' => $usuarios,
            'roles' => $roles
        ]);
    }

    // Actualizar Rol y Estatus
    public function update(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {
                // 1. Actualizamos estatus en la tabla users
                DB::table('users')->where('id', $id)->update([
                    'estatus' => $request->estatus
                ]);

                // 2. Actualizamos el rol
                // Primero vemos si ya tiene un registro en roles_usuarios
                $existeRol = DB::table('roles_usuarios')->where('id_usuario', $id)->first();

                if ($existeRol) {
                    DB::table('roles_usuarios')->where('id_usuario', $id)->update([
                        'id_rol' => $request->id_rol,
                        'updated_at' => now()
                    ]);
                } else {
                    DB::table('roles_usuarios')->insert([
                        'id_usuario' => $id,
                        'id_rol' => $request->id_rol,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $this->guardarLog('usuarios', 'editar', $id, ['nuevo_rol' => $request->id_rol, 'nuevo_estatus' => $request->estatus]);
            });

            return response()->json(['valid' => true, 'message' => 'Usuario actualizado correctamente.']);

        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
}
