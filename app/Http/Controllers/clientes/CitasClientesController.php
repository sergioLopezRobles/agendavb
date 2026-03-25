<?php

namespace App\Http\Controllers\clientes;
// IMPORTACIÓN DE CLASES NECESARIAS: CONTROLADOR BASE, FACHADA DE BASE DE DATOS Y LOGS
use App\Clases\GlobalFuncion;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitasClientesController extends Controller
{
    public function citasclientes($slug)
    {
        // BUSCA EL NEGOCIO EN LA BASE DE DATOS USANDO EL SLUG DE LA URL
        $negocio = DB::table('negocios')->where('slug', $slug)->first();
        // SI EL NEGOCIO NO EXISTE, DEVUELVE UN ERROR 404
        if (!$negocio) {
            abort(404);
        }

        // CONSULTA A LA TABLA 'CITAS' FILTRANDO POR EL ID DE NEGOCIO 1
        $citas = DB::table('citas')->where('id_negocio', $negocio->id)->get();

        // CONSULTA A LA TABLA 'SERVICIOS' FILTRANDO POR EL ID DE NEGOCIO 1
        $servicios = DB::table('servicios')->where('id_negocio', $negocio->id)->get();

        // REGISTRO DE LOS DATOS OBTENIDOS EN LOS LOGS
        Log::info('citas: ' . $citas);
        Log::info('servicios: ' . $servicios);

        // RETORNO DE UNA RESPUESTA EN FORMATO JSON CON LOS DATOS Y ESTADO DE VALIDACIÓN
        return response()->json(['valid' => true, 'citas' => $citas, 'servicios' => $servicios, 'nombre_negocio' => $negocio->nombre]);
    }

    // REGISTRA UNA NUEVA CITA EN LA BASE DE DATOS VALIDANDO LA EXISTENCIA DEL SERVICIO
    public function registrarcitacliente(Request $request)
    {
        try {
            // IDENTIFICA EL NEGOCIO MEDIANTE EL SLUG ENVIADO EN LA PETICIÓN
            $negocio = DB::table('negocios')->where('slug', $request->slug)->first();

            // BUSCA EL SERVICIO SOLICITADO PARA OBTENER INFORMACIÓN COMO EL PRECIO
            $servicioSeleccionado = DB::table('servicios')->where('id', $request->id_servicio)->get();

            // VERIFICA SI EL SERVICIO EXISTE EN LA BASE DE DATOS
            if ($servicioSeleccionado != null) {
                // EXISTE SERVICIO
                $anticipo = $servicioSeleccionado[0]->anticipo;

                // REALIZAR PAGO CON STRIPE SOLO SI REQUIERE ANTICIPO
                if (!empty($anticipo) && $anticipo > 0) {
                    $globalFuncion = new GlobalFuncion();
                    $respuestaPago = $globalFuncion->pagoUnicoStripe($request->cliente_email, $negocio->id, $request->id_servicio, $request->payment_method_id, $anticipo);

                    if (!$respuestaPago['valid']) {
                        // 🔥 Si requiere autenticación (3D Secure)
                        if (isset($respuestaPago['requires_action']) && $respuestaPago['requires_action']) {
                            return response()->json([
                                'requires_action' => true,
                                'client_secret' => $respuestaPago['client_secret']
                            ]);
                        }
                        // ❌ Error normal
                        return response()->json([
                            'valid' => false,
                            'message' => $respuestaPago['message']
                        ], 400);
                    }
                }

                // INSERCIÓN DE LOS DATOS DE LA CITA EN LA TABLA 'CITAS'
                DB::table('citas')->insert([
                    'id_negocio' => $negocio->id,
                    'id_servicio' => $request->id_servicio,
                    'cliente_nombre' => $request->cliente_nombre,
                    'cliente_telefono' => $request->cliente_telefono,
                    'cliente_email' => $request->cliente_email,
                    'fecha' => $request->fecha,
                    'hora' => $request->hora,   // HORA SELECCIONADA POR EL CLIENTE
                    'anticipo' => $anticipo,
                    'total' => $servicioSeleccionado[0]->precio,    // PRECIO OBTENIDO DE LA BD
                    'estado' => '0',
                    'recordatorio_enviado' => '0',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                // 1. PREPARAMOS LOS DATOS BASICOS QUE SE REPITEN
                $fechaFormateada = Carbon::parse($request->fecha)->format('d/m/Y'); // Formato: día/mes/año
                $hora = $request->hora;
                $correo = $request->cliente_email;
                $telefono = $request->cliente_telefono;
                $idNegocio = $negocio->id;

                // 2. CREAMOS Y GUARDAMOS EL PRIMER MOVIMIENTO (SIEMPRE SE GUARDA)
                $mensajeCita = "Se agendo cita el {$fechaFormateada}, a las {$hora}, cliente: {$correo} y {$telefono}";

                DB::table('movimientos_clientes')->insert([
                    'id_negocio' => $idNegocio,
                    'movimiento' => $mensajeCita,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // 3. VERIFICAMOS SI HAY ANTICIPO PARA GUARDAR EL SEGUNDO MOVIMIENTO
                // USAR EL ANTICIPO DE LA BD, NO DEL REQUEST
                if (!empty($anticipo) && $anticipo > 0) {

                    $mensajeAnticipo = "Se agrego anticipo de {$anticipo}, cliente: {$correo} y {$telefono}";

                    DB::table('movimientos_clientes')->insert([
                        'id_negocio' => $idNegocio,
                        'movimiento' => $mensajeAnticipo,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                // RETORNO DE CONFIRMACIÓN DE CREACIÓN
                return response()->json([
                    'valid' => true,
                    'message' => 'Se creo correctamente la cita'
                ]);
            } else {
                // NO EXISTE SERVICIO
                // RESPUESTA EN CASO DE QUE EL ID DEL SERVICIO NO SEA VÁLIDO
                return response()->json([
                    'valid' => false,
                    'message' => 'No existe el servicio'
                ]);
            }

        } catch (\Exception $e) {
            // CAPTURA DE CUALQUIER ERROR DURANTE EL PROCESO Y RETORNO DE ERROR 500
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //CALCULA LOS HORARIOS DISPONIBLES CONSIDERANDO DURACIÓN DEL SERVICIO Y CITAS EXISTENTES
    public function horariosdisponibles(Request $request){

        $id_servicio = $request->id_servicio;
        $fecha = $request->fecha;

        // 🔹 Servicio solicitado
        $servicio = DB::table('servicios')->where('id', $id_servicio)->first();
        if (!$servicio) {
            return response()->json(['valid' => false, 'message' => 'El servicio no existe']);
        }

        $duracionServicio = $servicio->duracion_minutos;

        // 🔹 Negocio
        $negocio = DB::table('negocios')->where('slug', $request->slug)->first();
        if (!$negocio) {
            return response()->json(['valid' => false, 'message' => 'El negocio no existe']);
        }

        // 🔹 Horarios del negocio
        $horarios_negocio = DB::table('horarios_negocios')
            ->where('id_negocio', $negocio->id)
            ->get();

        // 🔹 Citas del día
        $citas = DB::table('citas')
            ->where('fecha', $fecha)
            ->where('id_negocio', $negocio->id)
            ->get();

        // 🔹 Obtener duraciones de servicios (evita N+1)
        $servicios = DB::table('servicios')->pluck('duracion_minutos', 'id');

        // 🔹 Convertir citas en bloques ocupados
        $bloquesOcupados = [];

        foreach ($citas as $cita) {
            $inicio = Carbon::parse($cita->hora);
            $duracion = $servicios[$cita->id_servicio] ?? 0;
            $fin = $inicio->copy()->addMinutes($duracion);

            $bloquesOcupados[] = [
                'inicio' => $inicio,
                'fin' => $fin
            ];
        }

        $horariosDisponibles = [];

        foreach ($horarios_negocio as $horario) {

            $inicioJornada = Carbon::parse($horario->hora_inicio);
            $finJornada = Carbon::parse($horario->hora_fin);

            // 🔹 Ordenar bloques ocupados
            usort($bloquesOcupados, function ($a, $b) {
                return $a['inicio']->gt($b['inicio']);
            });

            $cursor = $inicioJornada->copy();

            foreach ($bloquesOcupados as $bloque) {

                // Si el bloque está fuera del horario, ignorar
                if ($bloque['fin'] <= $inicioJornada || $bloque['inicio'] >= $finJornada) {
                    continue;
                }

                // Ajustar a jornada
                $inicioBloque = $bloque['inicio']->copy()->max($inicioJornada);
                $finBloque = $bloque['fin']->copy()->min($finJornada);

                // 🔹 BLOQUE LIBRE antes de la cita
                if ($cursor < $inicioBloque) {

                    $inicioLibre = $cursor->copy();
                    $finLibre = $inicioBloque->copy();

                    // Generar horarios encadenados
                    while ($inicioLibre->copy()->addMinutes($duracionServicio) <= $finLibre) {

                        // ❌ bloquear horas pasadas
                        if ($fecha == Carbon::today()->format('Y-m-d') &&
                            $inicioLibre->lessThan(Carbon::now())) {
                            $inicioLibre->addMinutes($duracionServicio);
                            continue;
                        }

                        $horaInicio = $inicioLibre->format('H:i');
                        $horaFin = $inicioLibre->copy()->addMinutes($duracionServicio)->format('H:i');

                        $horariosDisponibles[] = [
                            'inicio' => $horaInicio,
                            'fin' => $horaFin,
                            'label' => $horaInicio . ' - ' . $horaFin
                        ];

                        // 🔥 CLAVE: avanzar por duración (NO intervalos)
                        $inicioLibre->addMinutes($duracionServicio);
                    }
                }

                // mover cursor al final del bloque ocupado
                $cursor = $finBloque->copy()->max($cursor);
            }

            // 🔹 BLOQUE LIBRE final
            if ($cursor < $finJornada) {

                $inicioLibre = $cursor->copy();
                $finLibre = $finJornada->copy();

                while ($inicioLibre->copy()->addMinutes($duracionServicio) <= $finLibre) {

                    if ($fecha == Carbon::today()->format('Y-m-d') &&
                        $inicioLibre->lessThan(Carbon::now())) {
                        $inicioLibre->addMinutes($duracionServicio);
                        continue;
                    }

                    $horaInicio = $inicioLibre->format('H:i');
                    $horaFin = $inicioLibre->copy()->addMinutes($duracionServicio)->format('H:i');

                    $horariosDisponibles[] = [
                        'inicio' => $horaInicio,
                        'fin' => $horaFin,
                        'label' => $horaInicio . ' - ' . $horaFin
                    ];

                    $inicioLibre->addMinutes($duracionServicio);
                }
            }
        }

        return response()->json([
            'valid' => true,
            'horariosDisponibles' => $horariosDisponibles
        ]);
    }
}
