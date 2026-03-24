<?php

namespace App\Http\Controllers\stripecard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Illuminate\Support\Str;

class NegocioController extends Controller
{
    public function registrarPlanNegocio(Request $request)
    {
        try {
            // ======================================================
            // 1. LÓGICA DE STRIPE (SUSCRIPCIONES RECURRENTES) ENCENDIDA
            // ======================================================
            // COMENTA DESDE AQUÍ

            Stripe::setApiKey(config('services.stripe.secret'));

            // a) Mapear los planes a los IDs de Precio de Stripe
            $preciosStripe = [
                1 => 'price_1T8vySCPQ2Qy65AdXblJIcCw', // Básico
                2 => 'price_1T8vyrCPQ2Qy65AdxgyLrYm3', // Medio
                3 => 'price_1T8vz5CPQ2Qy65AdLIyKXr8M'  // Avanzado
            ];

            $priceId = $preciosStripe[$request->plan] ?? 'price_1T8vySCPQ2Qy65AdXblJIcCw';

            // b) Crear el Cliente en Stripe y asignarle la tarjeta
            $customer = Customer::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'payment_method' => $request->payment_method_id,
                'invoice_settings' => [
                    'default_payment_method' => $request->payment_method_id,
                ],
            ]);

            // c) Crear la Suscripción recurrente
            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items' => [
                    ['price' => $priceId],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // d) GUARDAR LA SUSCRIPCIÓN EN TU BASE DE DATOS
            DB::table('suscripciones_stripe')->insert([
                'id_usuario' => Auth::id(),
                'nombre' => 'Plan ' . $request->plan,
                'stripe_id' => $subscription->id,
                'stripe_status' => $subscription->status,
                'stripe_price' => $priceId,
                'quantity' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            //HASTA AQUÍ

            // ======================================================
            // 2. GUARDAR EL NEGOCIO EN TU BASE DE DATOS
            // ======================================================

            // ACTUALIZAR AL USUARIO
            DB::table('users')
                ->where('id', Auth::id())
                ->update(['id_plan' => $request->plan]);

            // GENERAR SLUG
            $slugFinal = $request->slug;
            if (empty($slugFinal)) {
                $slugBase = \Illuminate\Support\Str::slug(substr($request->nombre, 0, 12));
                $slugFinal = $slugBase . '-' . strtolower(\Illuminate\Support\Str::random(4));
            }

            // INSERTAR EL NEGOCIO
            $idNegocio = DB::table('negocios')->insertGetId([
                'id_usuario' => Auth::id(),
                'id_plan' => $request->plan,
                'nombre' => $request->nombre,
                'email' => $request->email,
                'slug' => $slugFinal,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            // INSERTAR TELÉFONOS
            if ($request->has('telefonos') && is_array($request->telefonos)) {
                $telefonosInsert = [];
                foreach ($request->telefonos as $tel) {
                    if (!empty($tel['numero'])) {
                        $telefonosInsert[] = [
                            'id_negocio' => $idNegocio,
                            'id_tipo_numero_telefono' => $tel['id_tipo'],
                            'numero_telefono' => $tel['numero'],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ];
                    }
                }
                if (count($telefonosInsert) > 0) {
                    DB::table('numeros_telefonos_negocio')->insert($telefonosInsert);
                }
            }

            // INSERTAR HORARIOS
            if ($request->has('horarios') && is_array($request->horarios)) {
                $horariosInsert = [];
                foreach ($request->horarios as $horario) {
                    $partes = explode(' - ', $horario);
                    if (count($partes) == 2) {
                        $horariosInsert[] = [
                            'id_negocio' => $idNegocio,
                            'hora_inicio' => trim($partes[0]),
                            'hora_fin' => trim($partes[1]),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ];
                    }
                }
                if (count($horariosInsert) > 0) {
                    DB::table('horarios_negocios')->insert($horariosInsert);
                }
            }

            return response()->json([
                'valid' => true,
                'message' => '¡Suscripción aprobada y registrada!'
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'valid' => false,
                'message' => 'El pago fue rechazado: ' . $e->getError()->message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }
}
