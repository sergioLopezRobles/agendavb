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
            $table->decimal('precio', 10, 2);
            $table->decimal('anticipo', 10, 2)->nullable();
            $table->string('tarjeta')->default('0'); // 0 = Efectivo, 1 = Tarjeta
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
