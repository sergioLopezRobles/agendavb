<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class respaldocalendario extends Command
{
    // El parámetro ahora es OPCIONAL (gracias al signo de interrogación o dejándolo sin valor por defecto)
    protected $signature = 'app:respaldocalendario {--plan= : El ID del plan a procesar (Opcional)}';
    protected $description = 'Envía el respaldo de la agenda por correo a los usuarios de planes Medio y Avanzado';

    public function handle()
    {
        $this->info("Iniciando envío de respaldos de agenda...");

        // Obtenemos qué día de la semana es hoy (0 = Domingo, 1 = Lunes...)
        $hoy = \Carbon\Carbon::now()->dayOfWeek;

        // Buscamos solo a los usuarios cuyo plan incluya el día de hoy
        $usuarios = DB::table('users')
            ->join('plan_usuarios', 'users.id', '=', 'plan_usuarios.id_usuario')
            ->join('caracteristicasplanes', function($join) use ($hoy) {
                $join->on('plan_usuarios.id_plan', '=', 'caracteristicasplanes.id_plan')
                    ->where('caracteristicasplanes.titulo', 'dias_respaldo_calendario')
                    // FIND_IN_SET busca el número de hoy (ej. '1') dentro de la cadena '1,4,0' o '0,1,2,3,4,5,6'
                    ->whereRaw("FIND_IN_SET(?, caracteristicasplanes.valor)", [$hoy]);
            })
            ->select('users.id', 'users.email')
            ->distinct()
            ->get();

        if ($usuarios->isEmpty()) {
            $this->info("Hoy no hay usuarios programados para recibir respaldo.");
            return;
        }

        foreach ($usuarios as $usuario) {
            $this->info("Generando Excel y enviando correo a: {$usuario->email}");

            // 1. Llamamos a la función global
            $globalFunctions = new \App\Clases\GlobalFuncion();
            $spreadsheet = $globalFunctions->generarExcelAgenda($usuario->id);

            if ($spreadsheet) {
                // 2. Guardamos temporalmente
                $fileName = 'Respaldo_Agenda_' . \Carbon\Carbon::now()->format('Y-m-d') . '.xlsx';
                $tempPath = storage_path('app/public/' . $fileName);
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($tempPath);

                // 3. Enviamos por correo
                // (Asegúrate de que 'RespaldoAgendaMail' esté importado arriba en el archivo)
                \Illuminate\Support\Facades\Mail::to($usuario->email)
                    ->send(new \App\Mail\RespaldoAgendaMail($usuario, $tempPath, $fileName));

                // 4. Borramos el archivo temporal
                if(file_exists($tempPath)){
                    unlink($tempPath);
                }
            } else {
                $this->info("El usuario {$usuario->email} no tiene negocios/citas. Se omitió.");
            }
        }

        $this->info("Envío de respaldos finalizado.");
    }
}
