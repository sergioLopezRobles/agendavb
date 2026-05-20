<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\WhatsAppService;

class recordatorioscitaswhatsapp extends Command
{
    protected $signature = 'app:recordatorioscitaswhatsapp';
    protected $description = 'Envía recordatorios de WhatsApp usando los minutos dinámicos de caracteristicasplanes';

    public function handle()
    {
        Log::info('--- Ejecutando Escaneo de Citas ---');

        $ahora = Carbon::now('America/Mazatlan');

        $citas = DB::table('citas')
            ->join('negocios', 'citas.id_negocio', '=', 'negocios.id')
            ->join('caracteristicasplanes', function($join) {
                $join->on('negocios.id_plan', '=', 'caracteristicasplanes.id_plan')
                    ->where('caracteristicasplanes.titulo', '=', 'recordatorio_minutos');
            })
            ->leftJoin('servicios', 'citas.id_servicio', '=', 'servicios.id')
            ->select(
                'citas.*',
                'negocios.nombre as negocio_nombre',
                'caracteristicasplanes.valor as minutos_plan',
                'servicios.nombre as servicio_nombre'
            )
            ->where('citas.id_estado', '0')
            ->where('citas.recordatorio_enviado', '0')
            ->get();

        $waService = new WhatsAppService();

        foreach ($citas as $cita) {
            try {
                $fechaHoraCita = Carbon::parse($cita->fecha . ' ' . $cita->hora, 'America/Mazatlan');

                //3.LÓGICA DE VENTANA DE TIEMPO
                $minutosParaLaCita = $ahora->diffInMinutes($fechaHoraCita, false);
                $limitePlan = (int)$cita->minutos_plan;

                //Solo evaluar si estamos en la ventana de tiempo correcta
                if ($minutosParaLaCita > 0 && $minutosParaLaCita <= $limitePlan) {

                    //VALIDACIÓN DE CRÉDITOS ANTES DE ENVIAR NADA
                    $idUsuarioDueno = DB::table('negocios')->where('id', $cita->id_negocio)->value('id_usuario');
                    $idPlanDueno = DB::table('plan_usuarios')->where('id_usuario', $idUsuarioDueno)->value('id_plan');

                    $limiteCreditosSaaS = (int) DB::table('caracteristicasplanes')
                        ->where('id_plan', $idPlanDueno)
                        ->where('titulo', 'whatsapp_creditos_iniciales')
                        ->value('valor');

                    $creditosGastados = DB::table('creditos_whatsapp_negocio as cw')
                        ->join('negocios as n', 'cw.id_negocio', '=', 'n.id')
                        ->where('n.id_usuario', $idUsuarioDueno)
                        ->whereMonth('cw.created_at', $ahora->month)
                        ->whereYear('cw.created_at', $ahora->year)
                        ->count();

                    // Si ya se acabaron los creditos, se cancela
                    if ($creditosGastados >= $limiteCreditosSaaS) {
                        Log::warning("Cita {$cita->id} omitida. El usuario {$idUsuarioDueno} agotó sus {$limiteCreditosSaaS} créditos.");

                        // Marcamos como '2' para saber que falló por falta de créditos y no se quede en bucle
                        DB::table('citas')->where('id', $cita->id)->update([
                            'recordatorio_enviado' => '2',
                            'updated_at' => Carbon::now('America/Mazatlan')
                        ]);
                        continue; // Saltamos a la siguiente cita del ciclo
                    }

                    // --- Buscar teléfonos ---
                    $telefonosNegocio = DB::table('numeros_telefonos_negocio')
                        ->join('tipos_numero_telefono', 'numeros_telefonos_negocio.id_tipo_numero_telefono', '=', 'tipos_numero_telefono.id')
                        ->where('numeros_telefonos_negocio.id_negocio', $cita->id_negocio)
                        ->select('numeros_telefonos_negocio.numero_telefono', 'tipos_numero_telefono.tipo_numero_telefono')
                        ->get();

                    $textoTelefonos = "";
                    if ($telefonosNegocio->isEmpty()) {
                        $textoTelefonos = "nuestros medios oficiales";
                    } else {
                        $arregloTelefonos = [];
                        foreach ($telefonosNegocio as $tel) {
                            $arregloTelefonos[] = $tel->tipo_numero_telefono . ": " . $tel->numero_telefono;
                        }
                        $textoTelefonos = implode(", ", $arregloTelefonos);
                    }

                    $telefonoParaWA = '52' . $cita->cliente_telefono;

                    // 4. LLAMADA AL SERVICIO (Envío a Meta)
                    $waService->enviarRecordatorioCita(
                        $telefonoParaWA,
                        $cita->cliente_nombre,
                        $cita->fecha,
                        $cita->hora,
                        $cita->servicio_nombre ?? 'Servicio Programado',
                        $cita->negocio_nombre,
                        trim($textoTelefonos)
                    );

                    //5.REGISTRAMOS EL MOVIMIENTO EXITOSO
                    DB::transaction(function () use ($cita, $ahora) {
                        //Actualizamos la cita
                        DB::table('citas')->where('id', $cita->id)->update([
                            'recordatorio_enviado' => '1',
                            'updated_at' => $ahora
                        ]);

                        //Registramos el cobro del crédito
                        DB::table('creditos_whatsapp_negocio')->insert([
                            'id_negocio' => $cita->id_negocio,
                            'id_cita'    => $cita->id,
                            'created_at' => $ahora,
                            'updated_at' => $ahora
                        ]);
                    });

                    Log::info("Mensaje exitoso a {$cita->cliente_nombre}. Crédito descontado.");
                }

            } catch (\Exception $e) {
                Log::error("Error procesando cita {$cita->id}: " . $e->getMessage());
            }
        }
    }
}
