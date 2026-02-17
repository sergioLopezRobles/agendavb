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
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique(); //Ejemplo -> https://agendavb.com/barberia-lopez
            $table->string('telefono');
            $table->string('email');
            $table->string('id_plan');
            $table->string('whatsapp_creditos')->default('0');
            $table->string('hora_inicio')->nullable();
            $table->string('hora_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
