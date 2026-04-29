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

        // 1. IMPORTANTE: Asegúrate de que Carbon use la hora de México/Tepic
        $ahora = Carbon::now('America/Mazatlan');

        $citas = DB::table('citas')
            ->join('negocios', 'citas.id_negocio', '=', 'negocios.id')
            // Join con la tabla de características filtrando solo los minutos de recordatorio
            ->join('caracteristicasplanes', function($join) {
                $join->on('negocios.id_plan', '=', 'caracteristicasplanes.id_plan')
                    ->where('caracteristicasplanes.titulo', '=', 'recordatorio_minutos');
            })
            // Left Join para traer el nombre del servicio sin que desaparezcan las citas si hay error
            ->leftJoin('servicios', 'citas.id_servicio', '=', 'servicios.id')
            ->select(
                'citas.*',
                'negocios.nombre as negocio_nombre',
                'caracteristicasplanes.valor as minutos_plan',
                'servicios.nombre as servicio_nombre' //nombre del servicio
            )
            ->where('citas.id_estado', '0')
            ->where('citas.recordatorio_enviado', '0')
            ->get();

        $waService = new WhatsAppService();

        foreach ($citas as $cita) {
            try {
                // 2. PARSEAR HORA
                $fechaHoraCita = Carbon::parse($cita->fecha . ' ' . $cita->hora, 'America/Mazatlan');

                // 3. LÓGICA DE VENTANA DE TIEMPO
                $minutosParaLaCita = $ahora->diffInMinutes($fechaHoraCita, false);
                $limitePlan = (int)$cita->minutos_plan;

                // Solo enviar si la cita es a futuro y estamos dentro del tiempo del plan
                if ($minutosParaLaCita > 0 && $minutosParaLaCita <= $limitePlan) {

                    // --- NUEVA LÓGICA: Buscar los teléfonos del negocio ---
                    $telefonosNegocio = DB::table('numeros_telefonos_negocio')
                        ->join('tipos_numero_telefono', 'numeros_telefonos_negocio.id_tipo_numero_telefono', '=', 'tipos_numero_telefono.id')
                        ->where('numeros_telefonos_negocio.id_negocio', $cita->id_negocio)
                        ->select('numeros_telefonos_negocio.numero_telefono', 'tipos_numero_telefono.tipo_numero_telefono')
                        ->get();

                    // Armamos la lista en texto
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
                    // --------------------------------------------------------

                    $telefonoParaWA = '52' . $cita->cliente_telefono;

                    // Llamada al servicio con las 6 variables dinámicas
                    $waService->enviarRecordatorioCita(
                        $telefonoParaWA,
                        $cita->cliente_nombre,
                        $cita->fecha,
                        $cita->hora,
                        $cita->servicio_nombre ?? 'Servicio Programado', // Fallback por si lo borraron
                        $cita->negocio_nombre,
                        trim($textoTelefonos) // La lista armada (trim quita espacios o saltos de línea al final)
                    );

                    DB::table('citas')->where('id', $cita->id)->update([
                        'recordatorio_enviado' => '1',
                        'updated_at' => Carbon::now()
                    ]);

                    Log::info("Mensaje enviado a {$cita->cliente_nombre}. Cita en {$minutosParaLaCita} min. (Límite plan: {$limitePlan})");
                }

            } catch (\Exception $e) {
                Log::error("Error procesando cita {$cita->id}: " . $e->getMessage());
            }
        }
    }
}
