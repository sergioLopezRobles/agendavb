<?php

namespace App\Http\Controllers\citasnegocios;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CitasNegociosController extends Controller
{
    public function citasnegocios() {
        $idUsuario = Auth::id();
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        return response()->json([
            'valid' => true,
            'negocios' => $negocios
        ]);
    }

    public function obtenerCitasNegocio($id_negocio) {
        // Obtenemos las citas vinculadas al negocio y al usuario autenticado (por seguridad)
        $citas = DB::table('citas')
            ->where('id_negocio', $id_negocio)
            ->get();
        $estados = DB::table('estados_cita')->get();

        return response()->json([
            'valid' => true,
            'citas' => $citas,
            'estados' => $estados
        ]);
    }

    public function actualizarEstadoCita(Request $request) {
        // Validación básica para evitar errores
        if (!$request->id_cita || !$request->id_estado) {
            return response()->json(['valid' => false, 'message' => 'Datos incompletos']);
        }

        DB::table('citas')
            ->where('id', $request->id_cita)
            ->update([
                'id_estado' => $request->id_estado
            ]);

        return response()->json(['valid' => true, 'message' => 'Estado actualizado']);
    }

    // --- FUNCIÓN PARA EXCEL ---
    public function descargarExcel() {
        try {
            $idUsuario = Auth::id();

            // ================================================================
            // 1. VALIDACIÓN DE SEGURIDAD: VERIFICAR EL DÍA DEL PLAN
            // ================================================================
            $usuarioPlan = DB::table('plan_usuarios')->where('id_usuario', $idUsuario)->first();
            if (!$usuarioPlan) {
                return response()->json(['valid' => false, 'message' => 'No tienes un plan activo.'], 403);
            }

            $caracteristicaDias = DB::table('caracteristicasplanes')
                ->where('id_plan', $usuarioPlan->id_plan)
                ->where('titulo', 'dias_respaldo_calendario')
                ->first();

            // Si el plan no tiene la característica o es null (Plan Básico)
            if (!$caracteristicaDias || $caracteristicaDias->valor === null) {
                return response()->json(['valid' => false, 'message' => 'Tu plan no incluye descargas de respaldos de agenda.'], 403);
            }

            $hoy = \Carbon\Carbon::now()->dayOfWeek;
            $diasPermitidos = explode(',', $caracteristicaDias->valor);

            // Si el día de hoy no está en el arreglo de su plan (ej. 1,4,0)
            if (!in_array((string)$hoy, $diasPermitidos)) {
                return response()->json(['valid' => false, 'message' => 'Hoy no es tu día asignado para descargar respaldos.'], 403);
            }
            // ================================================================

            // 2. GENERAMOS EL EXCEL
            $globalFunctions = new \App\Clases\GlobalFuncion();
            $spreadsheet = $globalFunctions->generarExcelAgenda($idUsuario);

            // Si regresó null, es porque no tiene negocios
            if (!$spreadsheet) {
                return response()->json(['valid' => false, 'message' => 'No tienes negocios registrados.'], 400);
            }

            // Limpieza extrema de la memoria RAM
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $fileName = 'Agenda_VistaBoreal_' . \Carbon\Carbon::now()->format('Y-m-d') . '.xlsx';

            // Descargamos
            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            return response()->json([
                'valid' => false,
                'message' => 'Error al generar el Excel: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

}
