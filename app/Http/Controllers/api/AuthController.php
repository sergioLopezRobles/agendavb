<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Traits\RegistraMovimientos;
use Carbon\Carbon;

class AuthController extends Controller
{
    use RegistraMovimientos;

    public function register(Request $request){
        try {
            $existeCorreo = DB::table('users')->where('email', $request->email)->exists();

            if($existeCorreo){
                return response()->json([
                    'valid' => false,
                    'message' => 'El correo ya existe'
                ]);
            }

            $user = null;

            // Transacción: O se crea el usuario Y el rol, o no se crea nada
            DB::transaction(function () use ($request, &$user) {

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'telefono' => $request->telefono
                ]);

                // Asignamos el Rol 2 (Dueño) por defecto
                DB::table('roles_usuarios')->insert([
                    'id_usuario' => $user->id,
                    'id_rol' => 2,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                // Registramos el log
                $this->guardarLog('usuarios', 'crear', $request->name, $request->all());

            }); // Fin Transacción

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'valid' => true,
                'message' => 'Registro exitoso',
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request){
        try {
            $user = User::where('email', $request->email)->first();

            if(!$user || !Hash::check($request->password, $user->password)){
                return response()->json([
                    'valid' => false,
                    'message' => 'Credenciales incorrectas'
                ]);
            }

            if(!$user || !Hash::check($request->password, $user->password)){
                return response()->json([
                    'valid' => false,
                    'message' => 'Credenciales incorrectas'
                ]);
            }

            if($user->estatus == 0){
                return response()->json([
                    'valid' => false,
                    'message' => 'Tu cuenta ha sido suspendida. Por favor contacta al administrador.'
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Obtenemos el plan actual
            $planUsuario = DB::table('plan_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_plan = $planUsuario ? $planUsuario->id_plan : null;

            // Obtenemos el rol actual para mandarlo a Vue
            $rolUsuario = DB::table('roles_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_rol = $rolUsuario ? $rolUsuario->id_rol : 2; // Por si hay usuarios viejos sin rol, forzamos el 2

            return response()->json([
                'valid' => true,
                'user' => $user,
                'token' => $token,
                'has_plan' => $planUsuario ? true : false
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function user(Request $request){
        try {
            $user = $request->user();

            // Refrescamos plan
            $planUsuario = DB::table('plan_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_plan = $planUsuario ? $planUsuario->id_plan : null;

            // Refrescamos rol
            $rolUsuario = DB::table('roles_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_rol = $rolUsuario ? $rolUsuario->id_rol : 2;

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'valid' => true,
            'message' => 'Logout exitoso'
        ]);
    }
}
