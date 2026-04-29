<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckRole
{
    /**
     * Handle an incoming request.
     * $roles son los IDs de los roles permitidos (Ej: 1 para Admin, 2 para Dueño)
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'No autenticado.'], 401);
        }

        // Buscamos el rol del usuario que intenta entrar
        $rolUsuario = DB::table('roles_usuarios')->where('id_usuario', $user->id)->first();
        $userRoleId = $rolUsuario ? $rolUsuario->id_rol : 2; // Si no tiene, asumimos que es Dueño (2)

        // Verificamos si su rol está en la lista
        if (!in_array($userRoleId, $roles)) {
            return response()->json([
                'valid' => false,
                'message' => 'Acceso denegado. No tienes permisos de Administrador.'
            ], 403);
        }

        return $next($request);
    }
}
