<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Clases\GlobalFuncion;
use App\Traits\RegistraMovimientos;

class DashboardController extends Controller
{
    use RegistraMovimientos;

    public function index(){
        $idUsuario = Auth::id();

        $usuarioLoggeado = DB::table('users as u')
            ->leftJoin('plan_usuarios as pu', 'u.id', '=', 'pu.id_usuario')
            ->select('pu.id_plan', 'u.email', 'u.name')
            ->where('u.id', $idUsuario)
            ->first();

        $cantidadNegocios = DB::table('negocios')->where('id_usuario', $idUsuario)->count();

        if($usuarioLoggeado != null){
            if($usuarioLoggeado->id_plan != null){
                $globalFuncion = new GlobalFuncion();
                $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado->id_plan);
                $prioridadesTicket = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();

                return response()->json([
                    'valid' => true,
                    'planAdquirido' => $planAdquirido,
                    'usuarioLoggeado' => $usuarioLoggeado,
                    'cantidadNegocios' => $cantidadNegocios,
                    'prioridadesTicket' => $prioridadesTicket
                ]);
            }else{
                return response()->json([
                    'valid' => true,
                    'planAdquirido' => null,
                    'usuarioLoggeado' => $usuarioLoggeado,
                    'cantidadNegocios' => $cantidadNegocios
                ]);
            }
        }

        return response()->json([
            'valid' => false,
            'planAdquirido' => null,
        ]);
    }

    public function misNegocios(Request $request){
        $idUsuario = Auth::id();

        $usuarioLoggeado = DB::table('users as u')
            ->leftJoin('plan_usuarios as pu', 'u.id', '=', 'pu.id_usuario')
            ->select('u.name', 'u.email', 'pu.id_plan')
            ->where('u.id', $idUsuario)
            ->first();

        $planAdquirido = null;
        if($usuarioLoggeado && $usuarioLoggeado->id_plan) {
            $globalFuncion = new GlobalFuncion();
            $planAdquirido = $globalFuncion->obtenerPlanCompleto($usuarioLoggeado->id_plan);
        }

        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        foreach ($negocios as $negocio) {
            // Adjuntar Horarios
            $horariosDB = DB::table('horarios_negocios')->where('id_negocio', $negocio->id)->get();
            $horariosArray = [];
            foreach ($horariosDB as $horario) {
                $horariosArray[] = $horario->hora_inicio . ' - ' . $horario->hora_fin;
            }
            $negocio->horarios = $horariosArray;

            // Adjuntar Teléfonos
            $telefonosDB = DB::table('numeros_telefonos_negocio')->where('id_negocio', $negocio->id)->get();
            $telefonosArray = [];
            foreach ($telefonosDB as $tel) {
                $telefonosArray[] = [
                    'id_tipo' => $tel->id_tipo_numero_telefono,
                    'numero' => $tel->numero_telefono
                ];
            }
            $negocio->telefonos = $telefonosArray;
        }

        return response()->json([
            'valid' => true,
            'usuarioLoggeado' => $usuarioLoggeado,
            'planAdquirido' => $planAdquirido,
            'negocios' => $negocios
        ]);
    }

    public function actualizarNegocio(Request $request, $id){
        try {
            $negocioMio = DB::table('negocios')
                ->where('id', $id)
                ->where('id_usuario', Auth::id())
                ->first();

            if(!$negocioMio){
                return response()->json([
                    'valid' => false,
                    'message' => 'No tienes permisos para editar este negocio'
                ]);
            }

            // Preparar datos base para actualizar
            $updateData = [
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'updated_at' => Carbon::now()
            ];

            // Procesar el nuevo Logo (si viene)
            if ($request->hasFile('logo')) {
                $carpetaDestino = base_path('../uploads/documentos/imagenes/');

                // Borrar logo anterior físicamente si existía
                if ($negocioMio->logo) {
                    $rutaLogoViejo = base_path('../' . $negocioMio->logo);
                    if (File::exists($rutaLogoViejo)) {
                        File::delete($rutaLogoViejo);
                    }
                }

                // Crear carpeta si no existe
                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

                // Guardar nuevo logo
                $archivo = $request->file('logo');
                $nombreArchivo = 'Foto-logo-' . $id . '-' . date('His') . '.' . $archivo->getClientOriginalExtension();
                $archivo->move($carpetaDestino, $nombreArchivo);

                // Asignar al arreglo de actualización
                $updateData['logo'] = 'uploads/documentos/imagenes/' . $nombreArchivo;
            }

            // Ejecutamos la actualización
            DB::table('negocios')
                ->where('id', $id)
                ->update($updateData);

            // ACTUALIZAMOS TELÉFONOS
            DB::table('numeros_telefonos_negocio')->where('id_negocio', $id)->delete();
            if ($request->has('telefonos') && is_array($request->telefonos)) {
                $telefonosInsert = [];
                foreach($request->telefonos as $tel){
                    if (!empty($tel['numero'])) {
                        $telefonosInsert[] = [
                            'id_negocio' => $id,
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

            // ACTUALIZAMOS HORARIOS
            DB::table('horarios_negocios')->where('id_negocio', $id)->delete();
            if ($request->has('horarios') && is_array($request->horarios)) {
                foreach($request->horarios as $horario){
                    $horas = explode(' - ', $horario);
                    if (count($horas) == 2) {
                        DB::table('horarios_negocios')->insert([
                            'id_negocio' => $id,
                            'hora_inicio' => trim($horas[0]),
                            'hora_fin' => trim($horas[1]),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }
                }
            }
            // 3. ¡AQUÍ REGISTRAMOS EL LOG!
            $this->guardarLog('negocios', 'editar', $request->nombre, $request->all());


            return response()->json([
                'valid' => true,
                'message' => '¡Negocio actualizado correctamente!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }
}
