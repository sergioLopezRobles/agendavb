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
use Illuminate\Support\Facades\File;
use App\Traits\RegistraMovimientos; // ── NUEVO

class NegocioController extends Controller
{
    use RegistraMovimientos; // ── NUEVO

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
            $this->guardarLog('negocios', 'crear', $request->nombre, [
                'id_negocio' => $idNegocio,
                'id_plan'    => $request->plan,
                'stripe_id'  => $subscription->id ?? null
            ]);

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

            // ── LOG: cambio de plan (se guarda como edición del plan) ─────────
            $this->guardarLog('negocios', 'editar', 'Upgrade al Plan ' . $request->plan, [
                'id_plan_nuevo'  => $request->plan,
                'stripe_price'   => $nuevoPlanStripe->stripe_price_id
            ]);

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

            // ── LOG: negocio extra creado desde panel (usuario ya tiene plan) ─
            $this->guardarLog('negocios', 'crear', $request->nombre, [
                'id_negocio' => $idNegocio,
                'id_plan'    => $planUsuario->id_plan
            ]);

            return response()->json([
                'valid'   => true,
                'message' => '¡Negocio agregado exitosamente!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Error al procesar: ' . $e->getMessage()], 500);
        }
    }
}
