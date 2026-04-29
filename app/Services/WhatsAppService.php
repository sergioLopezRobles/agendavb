<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $phoneId;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = env('WHATSAPP_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->baseUrl = env('WHATSAPP_URL') . '/' . env('WHATSAPP_VERSION');
    }


    public function enviarRecordatorioCita($telefono, $nombreCliente, $fecha, $hora, $servicio, $negocio, $listaTelefonos)
    {
        $response = Http::withToken($this->token)->post("{$this->baseUrl}/{$this->phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'template',
            'template' => [
                'name' => 'recordatorio_citas_negocios', // nombre de plantilla
                'language' => ['code' => 'es_MX'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            // Los ?? evitan que mandes un valor null que rompa la petición
                            ['type' => 'text', 'text' => $nombreCliente ?? 'Cliente'],
                            ['type' => 'text', 'text' => $negocio ?? 'Negocio'],
                            ['type' => 'text', 'text' => $servicio ?? 'Servicio'],
                            ['type' => 'text', 'text' => $fecha ?? 'Fecha'],
                            ['type' => 'text', 'text' => $hora ?? 'Hora'],
                            ['type' => 'text', 'text' => $listaTelefonos ?? 'Medios de contacto']
                        ]
                    ]
                ]
            ]
        ]);

        // --- EL DETECTOR DE ERRORES DE META ---
        if ($response->failed()) {
            Log::error('❌ ERROR DE META AL ENVIAR WHATSAPP: ' . $response->body());
        }

        return $response->json();
    }
}
