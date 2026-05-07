<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\RegistraMovimientos;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use RegistraMovimientos;

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = auth()->user();

        // Guardamos datos actuales para el log antes de sobreescribirlos
        $nombreAnterior = $user->name;
        $cambioFoto = false;

        // Actualizamos el nombre
        $user->name = $request->name;

        if ($request->hasFile('avatar')) {
            $cambioFoto = true;

            // 1. Borrar avatar anterior si existe físicamente
            if ($user->avatar) {
                $rutaVieja = base_path('../uploads/documentos/profile_pictures/' . $user->avatar);
                if (file_exists($rutaVieja)) {
                    unlink($rutaVieja);
                }
            }

            // 2. Definir carpeta y crear si no existe
            $carpetaDestino = base_path('../uploads/documentos/profile_pictures/');
            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }

            // 3. Procesar archivo
            $archivo = $request->file('avatar');
            // El número final es la hora exacta (His) para evitar duplicados
            $nombreArchivo = 'Avatar-' . $user->id . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
            $archivo->move($carpetaDestino, $nombreArchivo);

            // 4. Guardamos SOLO el nombre en la BD como pidió el Inge
            $user->avatar = $nombreArchivo;
        }

        $user->save();

        // ── REGISTRO DEL LOG ──────────────────────────────────────────
        $this->guardarLog('usuarios', 'editar', $user->id, [
            'nombre_anterior' => $nombreAnterior,
            'nombre_nuevo'    => $user->name,
            'actualizo_foto'  => $cambioFoto ? 'Sí' : 'No',
            'archivo_nuevo'   => $cambioFoto ? $user->avatar : 'Sin cambios'
        ]);
        // ──────────────────────────────────────────────────────────────

        return response()->json([
            'valid' => true,
            'mensaje' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    }
}
