<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('precio');
            $table->string('descripcion');
            $table->string('limite_citas_mes')->nullable();
            $table->string('limite_servicios')->nullable();
            $table->string('intervalo_citas_minutos');
            $table->string('recordatorio_minutos');
            $table->string('permite_anticipo')->default('0'); //0 - No, 1 - Si
            $table->string('permite_reembolso')->default('0'); //0 - No, 1 - Si
            $table->string('whatsapp_creditos_iniciales')->default('0');
            $table->timestamps();
        });

        DB::table('planes')->insert([
            [
                'nombre' => 'Basico',
                'precio' => '100',
                'descripcion' => 'El basico',
                'limite_citas_mes' => 100,
                'limite_servicios' => 5,
                'intervalo_citas_minutos' => 20,
                'recordatorio_minutos' => 480,
                'permite_anticipo' => 0,
                'permite_reembolso' => 0,
                'whatsapp_creditos_iniciales' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Medio',
                'precio' => '200',
                'descripcion' => 'El medio',
                'limite_citas_mes' => 500,
                'limite_servicios' => 15,
                'intervalo_citas_minutos' => 5,
                'recordatorio_minutos' => 180,
                'permite_anticipo' => 1,
                'permite_reembolso' => 0,
                'whatsapp_creditos_iniciales' => 200,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Avanzado',
                'precio' => '500',
                'descripcion' => 'El perro',
                'limite_citas_mes' => null, //Infinitas
                'limite_servicios' => null, //Infinitas
                'intervalo_citas_minutos' => 1,
                'recordatorio_minutos' => 10,
                'permite_anticipo' => 1,
                'permite_reembolso' => 1,
                'whatsapp_creditos_iniciales' => 500,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
