<?php

namespace App\Http\Controllers\clientes;
// IMPORTACIÓN DE CLASES NECESARIAS: CONTROLADOR BASE, FACHADA DE BASE DE DATOS Y LOGS
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitasClientesController extends Controller
{
    public function citasclientes($slug){

        $negocio = DB::table('negocios')->where('slug', $slug)->first();

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
        return response()->json(['valid' => true, 'citas' => $citas, 'servicios' => $servicios]);
    }

    // REGISTRA UNA NUEVA CITA EN LA BASE DE DATOS VALIDANDO LA EXISTENCIA DEL SERVICIO
    public function registrarcitacliente(Request $request){
        try {

            $negocio = DB::table('negocios')->where('slug', $request->slug)->first();

            // BUSCA EL SERVICIO SOLICITADO PARA OBTENER INFORMACIÓN COMO EL PRECIO
            $servicioSeleccionado = DB::table('servicios')->where('id', $request->id_servicio)->get();

            // VERIFICA SI EL SERVICIO EXISTE EN LA BASE DE DATOS
            if($servicioSeleccionado != null){
                // EXISTE SERVICIO
                // INSERCIÓN DE LOS DATOS DE LA CITA EN LA TABLA 'CITAS'
                DB::table('citas')->insert([
                    'id_negocio' => $negocio->id,
                    'id_servicio' => $request->id_servicio,
                    'cliente_nombre' => $request->cliente_nombre,
                    'cliente_telefono' => $request->cliente_telefono,
                    'cliente_email' => $request->cliente_email,
                    'fecha' => $request->fecha,
                    'hora' => $request->hora,
                    'anticipo' => $request->anticipo,
                    'total' => $servicioSeleccionado[0]->precio,
                    'estado' => '0',
                    'recordatorio_enviado' => '0',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                // RETORNO DE CONFIRMACIÓN DE CREACIÓN
                return response()->json(['valid' => true, 'message' => 'Se creo correctamente la cita']);
            } else {
                // NO EXISTE SERVICIO
                // RESPUESTA EN CASO DE QUE EL ID DEL SERVICIO NO SEA VÁLIDO
                return response()->json(['valid' => false, 'message' => 'No existe el servicio']);
            }

        }catch (\Exception $e){
            // CAPTURA DE CUALQUIER ERROR DURANTE EL PROCESO Y RETORNO DE ERROR 500
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function horariosdisponibles(Request $request){
        $id_servicio = $request->id_servicio;
        $fecha = $request->fecha;

        $servicio = DB::table('servicios')->where('id', $id_servicio)->first();

        if (!$servicio) {
            return response()->json(['valid' => false, 'message' => 'El servicio no existe']);
        }

        $duracionServicio = $servicio->duracion_minutos;

        // OBTENER HORARIOS DEL NEGOCIO
        $negocio = DB::table('negocios')->where('slug', $request->slug)->first();
        if (!$negocio) {
            return response()->json(['valid' => false, 'message' => 'El negocio no existe']);
        }
        $horario_negocio = DB::table('horarios_negocios')->where('id_negocio', $negocio->id)->get();

        // Citas ya registradas ese día
        $citas = DB::table('citas')
            ->where('fecha', $fecha)
            ->where('id_negocio', $negocio->id)
            ->get();

        $horariosDisponibles = [];

        foreach ($horario_negocio as $horario) {
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);

            while($inicio->copy()->addMinutes($duracionServicio) <= $fin){

                $horaInicio = $inicio->format('H:i');
                $horaFin = $inicio->copy()->addMinutes($duracionServicio)->format('H:i');

                $disponible = true;

                // ❌ BLOQUEAR HORARIOS OCUPADOS
                foreach($citas as $cita){

                    $inicioCita = Carbon::parse($cita->hora);

                    // obtener duración del servicio de la cita existente
                    $servicioCita = DB::table('servicios')
                        ->where('id', $cita->id_servicio)
                        ->first();

                    $finCita = $inicioCita->copy()->addMinutes($servicioCita->duracion_minutos);

                    $inicioNueva = Carbon::parse($horaInicio);
                    $finNueva = Carbon::parse($horaFin);

                    // detectar traslape
                    if(
                        $inicioNueva < $finCita &&
                        $finNueva > $inicioCita
                    ){
                        $disponible = false;
                        break;
                    }
                }

                // ❌ BLOQUEAR HORAS PASADAS SI ES HOY
                if($fecha == Carbon::today()->format('Y-m-d')){

                    $ahora = Carbon::now();

                    if(Carbon::parse($horaInicio)->lessThan($ahora)){
                        $disponible = false;
                    }
                }

                if($disponible){

                    $horariosDisponibles[] = [
                        'inicio' => $horaInicio,
                        'fin' => $horaFin,
                        'label' => $horaInicio . ' - ' . $horaFin
                    ];
                }
                $inicio->addMinutes($duracionServicio);
            }
        }

        return response()->json(['valid' => true, 'horariosDisponibles' => $horariosDisponibles]);
    }
}
