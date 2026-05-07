<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = auth()->user();
        $user->name = $request->name;

        if ($request->hasFile('avatar')) {

            // 1. Borrar avatar anterior si existe
            if ($user->avatar) {
                $rutaVieja = base_path('../uploads/documentos/profile_pictures/' . $user->avatar);
                if (file_exists($rutaVieja)) {
                    unlink($rutaVieja);
                }
            }

            // 2. Definir la carpeta destino EXACTAMENTE igual que en negocios
            $carpetaDestino = base_path('../uploads/documentos/profile_pictures/');

            // 3. Crear la carpeta mágicamente si no existe
            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }

            // 4. Preparar archivo y moverlo
            $archivo = $request->file('avatar');
            $nombreArchivo = 'Avatar-' . $user->id . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
            $archivo->move($carpetaDestino, $nombreArchivo);

            // 5. ¡AQUÍ ESTÁ EL CAMBIO! Solo guardamos el nombre
            $user->avatar = $nombreArchivo;
        }

        $user->save();

        return response()->json([
            'valid' => true,
            'mensaje' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    }
}
