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
use App\Traits\RegistraMovimientos;

class NegocioController extends Controller
{
    use RegistraMovimientos;

    public function registrarPlanNegocio(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $planStripe = DB::table('planes_stripe')->where('id_plan', $request->plan)->first();

            if(!$planStripe) {
                return response()->json([
                    'valid' => false,
                    'message' => 'El plan seleccionado no está configurado correctamente en los pagos.'
                ]);
            }

            $priceId = $planStripe->stripe_price_id;

            // 1. PRIMERO HACEMOS EL COBRO EN STRIPE (Fuera de la transacción)
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

            // 2. SI STRIPE FUE EXITOSO, ABRIMOS LA TRANSACCIÓN PARA GUARDAR EN MÚLTIPLES TABLAS
            DB::transaction(function () use ($request, $subscription, $priceId) {

                DB::table('suscripciones_stripe')->insert([
                    'id_usuario'           => Auth::id(),
                    'nombre'               => 'Plan ' . $request->plan,
                    'stripe_id'            => $subscription->id ?? 'local_' . Str::random(10),
                    'stripe_status'        => $subscription->status ?? 'active',
                    'stripe_price'         => $priceId ?? null,
                    'current_period_start' => !empty($subscription->current_period_start)
                        ? Carbon::createFromTimestamp($subscription->current_period_start)
                        : Carbon::now(),
                    'current_period_end'   => !empty($subscription->current_period_end)
                        ? Carbon::createFromTimestamp($subscription->current_period_end)
                        : Carbon::now()->addMonth(),
                    'quantity'   => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                DB::table('plan_usuarios')->updateOrInsert(
                    ['id_usuario' => Auth::id()],
                    ['id_plan' => $request->plan, 'updated_at' => Carbon::now()]
                );

                $slugFinal = $request->slug ? Str::slug($request->slug) : null;
                if (empty($slugFinal)) {
                    $slugBase  = Str::slug(substr($request->nombre, 0, 12));
                    $slugFinal = $slugBase . '-' . strtolower(Str::random(4));
                }

                $idNegocio = DB::table('negocios')->insertGetId([
                    'id_usuario' => Auth::id(),
                    'id_plan'    => $request->plan,
                    'nombre'     => $request->nombre,
                    'email'      => $request->email,
                    'slug'       => $slugFinal,
                    'direccion'  => $request->direccion ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $rutaRelativaLogo = null;
                if ($request->hasFile('logo')) {
                    $carpetaDestino = base_path('../uploads/documentos/imagenes/');
                    if (!file_exists($carpetaDestino)) mkdir($carpetaDestino, 0777, true);
                    $archivo       = $request->file('logo');
                    $nombreArchivo = 'Foto-logo-' . $idNegocio . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
                    $archivo->move($carpetaDestino, $nombreArchivo);
                    $rutaRelativaLogo = 'uploads/documentos/imagenes/' . $nombreArchivo;
                    DB::table('negocios')->where('id', $idNegocio)->update(['logo' => $rutaRelativaLogo]);
                }

                if ($request->has('telefonos') && is_array($request->telefonos)) {
                    $telefonosInsert = [];
                    foreach ($request->telefonos as $tel) {
                        if (!empty($tel['numero'])) {
                            $telefonosInsert[] = [
                                'id_negocio'              => $idNegocio,
                                'id_tipo_numero_telefono' => $tel['id_tipo'],
                                'numero_telefono'         => $tel['numero'],
                                'created_at'              => Carbon::now(),
                                'updated_at'              => Carbon::now()
                            ];
                        }
                    }
                    if (count($telefonosInsert) > 0) DB::table('numeros_telefonos_negocio')->insert($telefonosInsert);
                }

                if ($request->has('horarios') && is_array($request->horarios)) {
                    $horariosInsert = [];
                    foreach ($request->horarios as $horario) {
                        $partes = explode(' - ', $horario);
                        if (count($partes) == 2) {
                            $horariosInsert[] = [
                                'id_negocio'  => $idNegocio,
                                'hora_inicio' => trim($partes[0]),
                                'hora_fin'    => trim($partes[1]),
                                'created_at'  => Carbon::now(),
                                'updated_at'  => Carbon::now()
                            ];
                        }
                    }
                    if (count($horariosInsert) > 0) DB::table('horarios_negocios')->insert($horariosInsert);
                }

                // ── LOG: negocio creado + suscripción inicial activada ────────────
                // (En PHP, $this está disponible automáticamente dentro del closure)
                $this->guardarLog('negocios', 'crear', $request->nombre, [
                    'id_negocio' => $idNegocio,
                    'id_plan'    => $request->plan,
                    'stripe_id'  => $subscription->id ?? null
                ]);

            }); // FIN DE LA TRANSACCIÓN DB

            return response()->json([
                'valid'   => true,
                'message' => '¡Suscripción aprobada y registrada!'
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'El pago fue rechazado: ' . $e->getError()->message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function upgradePlanStripe(Request $request)
    {
        try {
            $nuevoPlanStripe = DB::table('planes_stripe')->where('id_plan', $request->plan)->first();

            if(!$nuevoPlanStripe) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'El plan seleccionado no está configurado correctamente.'
                ]);
            }

            $suscripcionActual = DB::table('suscripciones_stripe')->where('id_usuario', Auth::id())->first();
            $subscriptionUpdated = null;

            // 1. ACTUALIZAMOS STRIPE PRIMERO
            if ($suscripcionActual) {
                Stripe::setApiKey(config('services.stripe.secret'));
                $subscription = Subscription::retrieve($suscripcionActual->stripe_id);
                Subscription::update($suscripcionActual->stripe_id, [
                    'items' => [[
                        'id'    => $subscription->items->data[0]->id,
                        'price' => $nuevoPlanStripe->stripe_price_id,
                    ]],
                    'proration_behavior' => 'always_invoice',
                ]);
                $subscriptionUpdated = Subscription::retrieve($suscripcionActual->stripe_id);
            }

            // 2. ABRIMOS LA TRANSACCIÓN DB PARA GUARDAR LOS CAMBIOS
            DB::transaction(function () use ($request, $suscripcionActual, $subscriptionUpdated, $nuevoPlanStripe) {

                if ($suscripcionActual && $subscriptionUpdated) {
                    DB::table('suscripciones_stripe')
                        ->where('id_usuario', Auth::id())
                        ->update([
                            'nombre'               => 'Plan ' . $request->plan,
                            'stripe_price'         => $nuevoPlanStripe->stripe_price_id,
                            'current_period_start' => Carbon::createFromTimestamp($subscriptionUpdated->current_period_start),
                            'current_period_end'   => Carbon::createFromTimestamp($subscriptionUpdated->current_period_end),
                            'updated_at'           => Carbon::now()
                        ]);
                }

                DB::table('plan_usuarios')->updateOrInsert(
                    ['id_usuario' => Auth::id()],
                    ['id_plan' => $request->plan, 'updated_at' => Carbon::now()]
                );

                DB::table('negocios')
                    ->where('id_usuario', Auth::id())
                    ->update(['id_plan' => $request->plan, 'updated_at' => Carbon::now()]);

                // ── LOG: cambio de plan ─────────
                $this->guardarLog('negocios', 'editar', 'Upgrade al Plan ' . $request->plan, [
                    'id_plan_nuevo'  => $request->plan,
                    'stripe_price'   => $nuevoPlanStripe->stripe_price_id
                ]);

            }); // FIN DE LA TRANSACCIÓN

            return response()->json([
                'valid'   => true,
                'message' => '¡Plan actualizado con éxito!'
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'El cobro del nuevo plan fue rechazado: ' . $e->getError()->message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al procesar el cambio de plan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearNegocioExtra(Request $request)
    {
        try {
            $user = Auth::user();

            $planUsuario = DB::table('plan_usuarios')->where('id_usuario', $user->id)->first();
            if (!$planUsuario) {
                return response()->json(['valid' => false, 'message' => 'No tienes un plan activo para crear negocios.']);
            }

            // ABRIMOS LA TRANSACCIÓN DIRECTAMENTE (Aquí no hay llamadas a Stripe)
            DB::transaction(function () use ($request, $user, $planUsuario) {

                $slugFinal = $request->slug
                    ? Str::slug($request->slug)
                    : Str::slug($request->nombre) . '-' . strtolower(Str::random(4));

                $idNegocio = DB::table('negocios')->insertGetId([
                    'id_usuario' => $user->id,
                    'id_plan'    => $planUsuario->id_plan,
                    'nombre'     => $request->nombre,
                    'email'      => $request->email,
                    'slug'       => $slugFinal,
                    'direccion'  => $request->direccion ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $rutaRelativaLogo = null;
                if ($request->hasFile('logo')) {
                    $carpetaDestino = base_path('../uploads/documentos/imagenes/');
                    if (!file_exists($carpetaDestino)) mkdir($carpetaDestino, 0777, true);
                    $archivo       = $request->file('logo');
                    $nombreArchivo = 'Foto-logo-' . $idNegocio . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
                    $archivo->move($carpetaDestino, $nombreArchivo);
                    $rutaRelativaLogo = 'uploads/documentos/imagenes/' . $nombreArchivo;
                    DB::table('negocios')->where('id', $idNegocio)->update(['logo' => $rutaRelativaLogo]);
                }

                if ($request->has('telefonos')) {
                    $telefonosInsert = [];
                    foreach ($request->telefonos as $tel) {
                        if (!empty($tel['numero'])) {
                            $telefonosInsert[] = [
                                'id_negocio'              => $idNegocio,
                                'id_tipo_numero_telefono' => $tel['id_tipo'],
                                'numero_telefono'         => $tel['numero'],
                                'created_at'              => Carbon::now()
                            ];
                        }
                    }
                    if (count($telefonosInsert) > 0) DB::table('numeros_telefonos_negocio')->insert($telefonosInsert);
                }

                if ($request->has('horarios')) {
                    $horariosInsert = [];
                    foreach ($request->horarios as $horario) {
                        $partes = explode(' - ', $horario);
                        if (count($partes) == 2) {
                            $horariosInsert[] = [
                                'id_negocio'  => $idNegocio,
                                'hora_inicio' => trim($partes[0]),
                                'hora_fin'    => trim($partes[1]),
                                'created_at'  => Carbon::now()
                            ];
                        }
                    }
                    if (count($horariosInsert) > 0) DB::table('horarios_negocios')->insert($horariosInsert);
                }

                // ── LOG: negocio extra creado desde panel ─
                $this->guardarLog('negocios', 'crear', $request->nombre, [
                    'id_negocio' => $idNegocio,
                    'id_plan'    => $planUsuario->id_plan
                ]);

            }); // FIN DE LA TRANSACCIÓN

            return response()->json([
                'valid'   => true,
                'message' => '¡Negocio agregado exitosamente!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Error al procesar: ' . $e->getMessage()], 500);
        }
    }
}
