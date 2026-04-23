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
                'name' => 'recordatorio_citas_negocios', //nombre de  plantilla
                'language' => ['code' => 'es_MX'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $nombreCliente],
                            ['type' => 'text', 'text' => $negocio],
                            ['type' => 'text', 'text' => $servicio],
                            ['type' => 'text', 'text' => $fecha],
                            ['type' => 'text', 'text' => $hora],
                            ['type' => 'text', 'text' => $listaTelefonos]
                        ]
                    ]
                ]
            ]
        ]);

        return $response->json();
    }
}
