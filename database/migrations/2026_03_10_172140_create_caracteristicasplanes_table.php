<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caracteristicasplanes', function (Blueprint $table) {
            $table->id();
            $table->string('id_plan');
            $table->string('titulo');
            $table->string('valor')->nullable();
            $table->timestamps();
        });

        DB::table('caracteristicasplanes')->insert([
            [
                'id_plan' => '1',
                'titulo' => 'precio',
                'valor' => '100',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'descripcion',
                'valor' => 'El basico, puedes crear solo 1 negocio',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'maximonegocios',
                'valor' => '1',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'limite_citas_mes',
                'valor' => '100',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'limite_servicios',
                'valor' => '5',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'intervalo_citas_minutos',
                'valor' => '20',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'recordatorio_minutos',
                'valor' => '480',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'permite_anticipo',
                'valor' => '0',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'permite_reembolso',
                'valor' => '0',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'whatsapp_creditos_iniciales',
                'valor' => '200',
            ],
            [
                'id_plan' => '1',
                'titulo' => 'created_at',
                'valor' => Carbon::now(),
            ],
            [
                'id_plan' => '1',
                'titulo' => 'updated_at',
                'valor' => Carbon::now(),
            ],


            [
                'id_plan' => '2',
                'titulo' => 'precio',
                'valor' => '200',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'descripcion',
                'valor' => 'El medio, puedes crear hasta 3 negocios',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'maximonegocios',
                'valor' => '3',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'limite_citas_mes',
                'valor' => '500',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'limite_servicios',
                'valor' => '15',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'intervalo_citas_minutos',
                'valor' => '5',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'recordatorio_minutos',
                'valor' => '180',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'permite_anticipo',
                'valor' => '1',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'permite_reembolso',
                'valor' => '0',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'whatsapp_creditos_iniciales',
                'valor' => '200',
            ],
            [
                'id_plan' => '2',
                'titulo' => 'created_at',
                'valor' => Carbon::now(),
            ],
            [
                'id_plan' => '2',
                'titulo' => 'updated_at',
                'valor' => Carbon::now(),
            ],


            [
                'id_plan' => '3',
                'titulo' => 'precio',
                'valor' => '500',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'descripcion',
                'valor' => 'El Avanzado, puedes crear hasta 6 negocios y URL personalizado',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'maximonegocios',
                'valor' => '6',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'limite_citas_mes',
                'valor' => null,
            ],
            [
                'id_plan' => '3',
                'titulo' => 'limite_servicios',
                'valor' => null,
            ],
            [
                'id_plan' => '3',
                'titulo' => 'intervalo_citas_minutos',
                'valor' => '1',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'recordatorio_minutos',
                'valor' => '10',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'permite_anticipo',
                'valor' => '1',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'permite_reembolso',
                'valor' => '1',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'whatsapp_creditos_iniciales',
                'valor' => '500',
            ],
            [
                'id_plan' => '3',
                'titulo' => 'created_at',
                'valor' => Carbon::now(),
            ],
            [
                'id_plan' => '3',
                'titulo' => 'updated_at',
                'valor' => Carbon::now(),
            ],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristicasplanes');
    }
};
