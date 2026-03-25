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
        Schema::create('ticket_soporte_usuarios_negocios', function (Blueprint $table) {
            $table->increments('indice');
            $table->string('id');
            $table->string('id_usuario');
            $table->string('id_negocio');
            //NUEVO CAMPO AGREGADO AQUÍ
            $table->integer('id_pregunta');
            // El asunto servirá para guardar el texto cuando elijan "Otro"
            $table->string('asunto');
            $table->string('id_prioridad')->nullable();
            $table->string('id_estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_soporte_usuarios_negocios');
    }
};
