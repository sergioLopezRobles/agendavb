<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RespaldoAgendaMail;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class respaldocalendario extends Command
{
    // El parámetro ahora es OPCIONAL (gracias al signo de interrogación o dejándolo sin valor por defecto)
    protected $signature = 'app:respaldocalendario {--plan= : El ID del plan a procesar (Opcional)}';
    protected $description = 'Envía el respaldo de la agenda por correo a los usuarios de planes Medio y Avanzado';

    public function handle()
    {
        $planId = $this->option('plan');

        // 1. Preparamos la consulta base
        $query = DB::table('users')
            ->join('negocios', 'users.id', '=', 'negocios.id_usuario')
            ->select('users.id', 'users.name', 'users.email')
            ->distinct();

            $query->whereIn('negocios.id_plan', [2, 3]);
            $this->info("Ejecutando modo manual: Procesando TODOS los usuarios con planes Pro (2 y 3).");

        $usuarios = $query->get();

        if ($usuarios->isEmpty()) {
            $this->info("No hay usuarios para respaldar en este momento.");
            return;
        }

        $estiloCabecera = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        foreach ($usuarios as $usuario) {
            $this->info("Generando Excel y enviando correo a: {$usuario->email}");

            // 1. LLAMAMOS A LA FUNCIÓN GLOBAL
            $globalFunctions = new \App\Clases\GlobalFuncion();
            $spreadsheet = $globalFunctions->generarExcelAgenda($usuario->id);

            // Verificamos por si este usuario tiene plan pro pero borró sus negocios
            if ($spreadsheet) {
                // 2. GUARDAMOS TEMPORALMENTE EL EXCEL
                $fileName = 'Respaldo_Agenda_' . Carbon::now()->format('Y-m-d') . '.xlsx';
                $tempPath = storage_path('app/public/' . $fileName);
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($tempPath);

                // 3. ENVIAMOS POR CORREO
                Mail::to($usuario->email)->send(new RespaldoAgendaMail($usuario, $tempPath, $fileName));

                // 4. BORRAMOS EL ARCHIVO TEMPORAL
                if(file_exists($tempPath)){
                    unlink($tempPath);
                }
            } else {
                $this->info("El usuario {$usuario->email} no tiene negocios activos. Se omitió el envío.");
            }
        }

        $this->info("Proceso finalizado. Se enviaron todos los correos.");
    }
}
