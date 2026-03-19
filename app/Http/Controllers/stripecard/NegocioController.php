<?php

namespace App\Http\Controllers\stripecard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Str;

class NegocioController extends Controller
{
    public function registrarPlanNegocio(Request $request)
    {
        try {
            // 1. CONFIGURAR STRIPE (LLAVE SECRETA DE PRUEBA)
            // Reemplaza esto con tu llave real de Stripe que empieza con sk_test_...
            Stripe::setApiKey( config('services.stripe.secret'));

            // 2. DEFINIR LOS PRECIOS SEGÚN EL PLAN
            // Stripe cobra en centavos. Si quieres cobrar $200.00 MXN, debes mandarle 20000.
            $precios = [
                1 => 10000, // Plan 1: $200.00 MXN
                2 => 20000, // Plan 2: $400.00 MXN
                3 => 50000  // Plan 3: $600.00 MXN
            ];

            $montoACobrar = $precios[$request->plan] ?? 20000; // Por defecto cobra el plan 1 si hay error

            // 3. EJECUTAR EL COBRO EN STRIPE
            // Usamos el "payment_method_id" que nos mandó Vue desde el front-end
            $paymentIntent = PaymentIntent::create([
                'amount' => $montoACobrar,
                'currency' => 'mxn',
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => 'http://localhost:8000/dashboard' // Requerido por Stripe
            ]);

            // ==========================================================
            // 4. SI EL CÓDIGO LLEGA HASTA AQUÍ, EL PAGO FUE APROBADO ✅
            // ==========================================================
            // --> ESTA ES LA LÍNEA NUEVA QUE FALTA: ACTUALIZAR AL USUARIO
            DB::table('users')
                ->where('id', Auth::id())
                ->update(['id_plan' => $request->plan]);

            // GENERAR SLUG CORTO AUTOMÁTICO SI VIENE VACÍO
            $slugFinal = $request->slug;
            if (empty($slugFinal)) {
                $slugBase = \Illuminate\Support\Str::slug(substr($request->nombre, 0, 12));
                $slugFinal = $slugBase . '-' . strtolower(\Illuminate\Support\Str::random(4));
            }

            // INSERTAR EL NEGOCIO (AQUÍ ESTÁ LA CORRECCIÓN CLAVE)
            $idNegocio = DB::table('negocios')->insertGetId([
                'id_usuario' => Auth::id(),
                'id_plan' => $request->plan,
                'nombre' => $request->nombre,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'slug' => $slugFinal, // <--- DEBE DECIR EXACTAMENTE ESTO, SIN EL $request->slug
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            // Insertar los horarios seleccionados
            if ($request->has('horarios') && is_array($request->horarios)) {
                $horariosInsert = [];
                foreach ($request->horarios as $horario) {
                    // Separar "09:00 - 10:00" en inicio y fin
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
                'message' => '¡Pago aprobado! Negocio registrado con éxito.'
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            // SI LA TARJETA ES RECHAZADA (Fondos insuficientes, robada, etc.)
            return response()->json([
                'valid' => false,
                'message' => 'El pago fue rechazado: ' . $e->getError()->message
            ]);
        } catch (\Exception $e) {
            // CUALQUIER OTRO ERROR DE SISTEMA
            return response()->json([
                'valid' => false,
                'message' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }
}
