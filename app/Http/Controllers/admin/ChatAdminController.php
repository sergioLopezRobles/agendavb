<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatAdminController extends Controller
{
    // 1. Obtener la lista de otros Administradores
    public function getContactos()
    {
        // Hacemos un JOIN con tu tabla pivote 'roles_usuarios'
        $admins = DB::table('users as u')
            ->join('roles_usuarios as ru', 'u.id', '=', 'ru.id_usuario')
            ->where('ru.id_rol', 1) // Buscamos a los que tengan el rol 1 (Administrador)
            ->where('u.id', '!=', auth()->id()) // Excluimos al usuario actual
            ->select('u.id', 'u.name', 'u.email')
            ->get();

        return response()->json([
            'valid' => true,
            'contactos' => $admins
        ]);
    }

    // 2. Obtener los mensajes privados entre el Admin loggeado y el contacto seleccionado
    public function getConversacion($id)
    {
        $miId = auth()->id();

        $mensajes = DB::table('mensajes_admin as m')
            ->select('m.id', 'm.mensaje', 'm.created_at', 'm.id_usuario', 'm.receptor_id')
            ->where(function($query) use ($miId, $id) {
                // Mensajes que yo envié a ese contacto
                $query->where('m.id_usuario', $miId)
                    ->where('m.receptor_id', $id);
            })
            ->orWhere(function($query) use ($miId, $id) {
                // Mensajes que ese contacto me envió a mí
                $query->where('m.id_usuario', $id)
                    ->where('m.receptor_id', $miId);
            })
            ->orderBy('m.created_at', 'asc')
            ->get();

        return response()->json([
            'valid' => true,
            'mensajes' => $mensajes
        ]);
    }

    // 3. Guardar el mensaje directo
    public function storePrivado(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string',
            'receptor_id' => 'required'
        ]);

        DB::table('mensajes_admin')->insert([
            'id_usuario' => auth()->id(),
            'receptor_id' => $request->receptor_id,
            'mensaje' => $request->mensaje,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['valid' => true]);
    }
}
