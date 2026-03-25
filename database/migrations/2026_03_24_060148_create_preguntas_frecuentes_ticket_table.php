<?php

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
        Schema::create('preguntas_frecuentes_ticket', function (Blueprint $table) {
            $table->increments('indice');
            $table->string('id');
            $table->string('pregunta');
            $table->timestamps();
        });

        // Insertamos los catálogos por defecto
        \Illuminate\Support\Facades\DB::table('preguntas_frecuentes_ticket')->insert([
            ['id' => 0, 'pregunta' => 'Otro (Especificar problema)', 'created_at' => now(), 'updated_at' => now()], // ID 0 para "Otro"
            ['id' => 1, 'pregunta' => 'Problemas al agendar citas', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'pregunta' => 'Problemas con la sincronización del calendario', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'pregunta' => 'Dudas sobre la facturación o mi plan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'pregunta' => 'Error al configurar mis servicios', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas_frecuentes_ticket');
    }
};
