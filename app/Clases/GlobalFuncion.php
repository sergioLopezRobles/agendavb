<?php

namespace App\Clases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class GlobalFuncion
{
    public function obtenerPlanCompleto($idPlan)
    {
        $plan = DB::table('planes')->where('id', $idPlan)->first();

        if (!$plan) {
            return null;
        }

        $caracteristicas = DB::table('caracteristicasplanes')
            ->where('id_plan', $idPlan)
            ->get();

        foreach ($caracteristicas as $c) {
            $plan->{$c->titulo} = $c->valor; //
        }

        return $plan;
    }

    public function obtenerAtributoPlan($idPlan, $atributo)
    {
        $valor = DB::table('caracteristicasplanes')
            ->where('id_plan', $idPlan)
            ->where('titulo', $atributo)
            ->value('valor');

        return $valor;
    }

    public static function generarIdRandom($length)
    {
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($caracteres);
        $randomId = '';
        for ($i = 0; $i < $length; $i++) {
            $randomId .= $caracteres[rAND(0, $charactersLength - 1)];
        }
        return $randomId;
    }

    public function pagoUnicoStripe($emailEmisor, $idNegocio, $idServicio, $payment_method_id, $montoUnico)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => ($montoUnico * 100),
                'currency' => 'mxn',
                'payment_method' => $payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'payment_method_types' => ['card'],
                'expand' => ['charges.data']
            ]);

            if ($paymentIntent->status === 'succeeded') {
                //PAGO ACEPTADO

                $charge = $paymentIntent->charges->data[0] ?? null;

                // INSERCIÓN DE LOS DATOS DE LA TABLA DE PAYMENTS
                DB::table('payments')->insert([
                    'id_negocio' => $idNegocio,
                    'id_servicio' => $idServicio,
                    'description' => 'Anticipo de cita',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_method_id' => $paymentIntent->payment_method,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'payer_email' => $emailEmisor,
                    'receipt_url' => $charge->receipt_url ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                return [
                    'valid' => true,
                    'message' => 'El pago fue aceptado'
                ];
            }

            if ($paymentIntent->status === 'requires_action') {
                return [
                    'valid' => false,
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret
                ];
            }

            return [
                'valid' => false,
                'message' => 'El pago no pudo ser procesado'
            ];

        } catch (\Stripe\Exception\CardException $e) {

            return [
                'valid' => false,
                'message' => 'El pago fue rechazado: ' . $e->getError()->message
            ];

        } catch (\Exception $e) {

            return [
                'valid' => false,
                'message' => 'Error general: ' . $e->getMessage()
            ];
        }
    }
}
