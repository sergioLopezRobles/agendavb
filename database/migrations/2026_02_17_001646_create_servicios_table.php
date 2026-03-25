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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('id_negocio');
            $table->string('nombre');
            // Cambiamos a decimal para poder calcular el porcentaje exacto sin errores
            $table->decimal('precio', 10, 2);
            // Nuevo campo para guardar de cuánto es el anticipo (Ej. si el precio es 1000 y el plan pide 10%, aquí se guarda 100.00)
            $table->decimal('anticipo', 10, 2)->nullable();
            $table->string('tarjeta')->default('0');
            $table->integer('duracion_minutos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
