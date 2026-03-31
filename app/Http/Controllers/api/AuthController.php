<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request){
        try {
            $existeCorreo = DB::select("SELECT email FROM users WHERE email = '$request->email'");

            if($existeCorreo != null){
                return response()->json([
                    'valid' => false,
                    'message' => 'El correo ya existe'
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telefono' => $request->telefono
            ]);

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

            $token = $user->createToken('auth_token')->plainTextToken;

            $planUsuario = DB::table('plan_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_plan = $planUsuario ? $planUsuario->id_plan : null;

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

            $planUsuario = DB::table('plan_usuarios')->where('id_usuario', $user->id)->first();
            $user->id_plan = $planUsuario ? $planUsuario->id_plan : null;

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
