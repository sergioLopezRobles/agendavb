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
    public function citasclientes(){
        // CONSULTA A LA TABLA 'CITAS' FILTRANDO POR EL ID DE NEGOCIO 1
        $citas = DB::table('citas')->where('id_negocio', '1')->get();

        // CONSULTA A LA TABLA 'SERVICIOS' FILTRANDO POR EL ID DE NEGOCIO 1
        $servicios = DB::table('servicios')->where('id_negocio', '1')->get();

        // REGISTRO DE LOS DATOS OBTENIDOS EN LOS LOGS
        Log::info('citas: ' . $citas);
        Log::info('servicios: ' . $servicios);

        // RETORNO DE UNA RESPUESTA EN FORMATO JSON CON LOS DATOS Y ESTADO DE VALIDACIÓN
        return response()->json(['valid' => true, 'citas' => $citas, 'servicios' => $servicios]);
    }

    // REGISTRA UNA NUEVA CITA EN LA BASE DE DATOS VALIDANDO LA EXISTENCIA DEL SERVICIO
    public function registrarcitacliente(Request $request){
        try {
            // BUSCA EL SERVICIO SOLICITADO PARA OBTENER INFORMACIÓN COMO EL PRECIO
            $servicioSeleccionado = DB::table('servicios')->where('id', $request->id_servicio)->get();

            // VERIFICA SI EL SERVICIO EXISTE EN LA BASE DE DATOS
            if($servicioSeleccionado != null){
                // EXISTE SERVICIO
                // INSERCIÓN DE LOS DATOS DE LA CITA EN LA TABLA 'CITAS'
                DB::table('citas')->insert([
                    'id_negocio' => '1',
                    'id_servicio' => $request->id_servicio,
                    'cliente_nombre' => $request->cliente_nombre,
                    'cliente_telefono' => $request->cliente_telefono,
                    'cliente_email' => $request->cliente_email,
                    'fecha' => $request->fecha,
                    'hora' => '13:00',
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
}
