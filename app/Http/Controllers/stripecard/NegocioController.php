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
            // COMENTA AQUI PARA DESACTIVAR STRIPE (BACKEND)
            /*
            Stripe::setApiKey(config('services.stripe.secret'));

            $planStripe = DB::table('planes_stripe')->where('id_plan', $request->plan)->first();

            if(!$planStripe) {
                return response()->json([
                    'valid' => false,
                    'message' => 'El plan seleccionado no está configurado correctamente en los pagos.'
                ]);
            }

            $priceId = $planStripe->stripe_price_id;

            $customer = Customer::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'payment_method' => $request->payment_method_id,
                'invoice_settings' => [
                    'default_payment_method' => $request->payment_method_id,
                ],
            ]);

            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items' => [
                    ['price' => $priceId],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

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
            */
            // HASTA AQUI

            DB::table('users')
                ->where('id', Auth::id())
                ->update(['id_plan' => $request->plan]);

            // GENERAR Y LIMPIAR EL SLUG (Evita espacios y caracteres especiales)
            $slugFinal = $request->slug ? \Illuminate\Support\Str::slug($request->slug) : null;

            if (empty($slugFinal)) {
                $slugBase = \Illuminate\Support\Str::slug(substr($request->nombre, 0, 12));
                $slugFinal = $slugBase . '-' . strtolower(\Illuminate\Support\Str::random(4));
            }

            $idNegocio = DB::table('negocios')->insertGetId([
                'id_usuario' => Auth::id(),
                'id_plan' => $request->plan,
                'nombre' => $request->nombre,
                'email' => $request->email,
                'slug' => $slugFinal,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

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
