<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Cache;


class TwilioController extends Controller
{
    public function enviarCodigo(Request $request)
    {
        $request->validate(['telefono' => 'required|string|size:10']);

        $numeroDestino = '+52' . $request->telefono;
        $codigo = rand(100000, 999999);

        Cache::put('codigo_verificacion_' . $request->telefono, $codigo, now()->addMinutes(10));

        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
            $twilio->messages->create(
                $numeroDestino,
                [
                    "from" => env('TWILIO_FROM'),
                    "body" => "Tu código de verificación para Vista Boreal es: " . $codigo
                ]
            );

            return response()->json(['success' => true, 'message' => 'Código enviado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|size:10',
            'codigo' => 'required|string'
        ]);

        $codigoGuardado = Cache::get('codigo_verificacion_' . $request->telefono);

        if ($codigoGuardado && $codigoGuardado == $request->codigo) {
            Cache::forget('codigo_verificacion_' . $request->telefono);
            return response()->json(['success' => true, 'message' => 'Número verificado']);
        }

        return response()->json(['success' => false, 'message' => 'Código incorrecto o expirado'], 400);
    }
}
