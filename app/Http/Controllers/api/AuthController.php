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

        $existeCorreo = DB::select("SELECT email FROM users WHERE email = '$request->email'");

        if($existeCorreo != null){
            //existe correo electronico
            Log::info('entro');
            return response()->json([
                'valid' => false,
                'message' => 'El correo ya existe'
            ]);
        }
        Log::info('entro 2');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'valid' => true,
            'message' => 'Registro exitoso',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function login(Request $request){
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request){
        return response()->json($request->user());
    }

    public function logout(Request $request){

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }
}
