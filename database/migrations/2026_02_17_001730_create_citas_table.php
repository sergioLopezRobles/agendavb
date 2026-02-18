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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('id_negocio');
            $table->string('id_servicio');
            $table->string('cliente_nombre');
            $table->string('cliente_telefono');
            $table->string('cliente_email')->nullable();
            $table->string('fecha');
            $table->string('hora');
            $table->string('anticipo')->default('0');
            $table->string('total')->default('0');
            $table->string('estado')->default('0'); //0 - Pendiente, 1 - En proceso, 2 - Terminado, 3 - Cancelado
            $table->string('recordatorio_enviado')->default('0'); //0 - False, 1 - True
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
