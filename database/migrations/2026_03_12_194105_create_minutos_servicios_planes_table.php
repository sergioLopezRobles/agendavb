<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minutos_servicios_planes', function (Blueprint $table) {
            $table->id();
            $table->string('id_plan');
            $table->string('minutos');
            $table->timestamps();
        });

        // --> arreglo para guardar todos los insert
        $datos = [];
        $ahora = Carbon::now();

        // --> plan 1 (basico): intervalos de 30 minutos hasta 120
        for ($i = 30; $i <= 120; $i += 30) {
            $datos[] = [
                'id_plan' => '1',
                'minutos' => (string)$i,
                'created_at' => $ahora,
                'updated_at' => $ahora
            ];
        }

        // --> plan 2 (medio): intervalos de 20 minutos hasta 120
        for ($i = 20; $i <= 120; $i += 20) {
            $datos[] = [
                'id_plan' => '2',
                'minutos' => (string)$i,
                'created_at' => $ahora,
                'updated_at' => $ahora
            ];
        }

        // --> plan 3 (avanzado): intervalos de 10 minutos hasta 120
        for ($i = 10; $i <= 120; $i += 10) {
            $datos[] = [
                'id_plan' => '3',
                'minutos' => (string)$i,
                'created_at' => $ahora,
                'updated_at' => $ahora
            ];
        }

        // --> insertamos todos los datos de golpe en la base de datos
        DB::table('minutos_servicios_planes')->insert($datos);
    }

    public function down(): void
    {
        Schema::dropIfExists('minutos_servicios_planes');
    }
};
