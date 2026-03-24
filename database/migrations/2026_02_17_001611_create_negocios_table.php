<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('id_usuario'); // Dueño del negocio
            $table->string('id_plan');    // Plan que eligió (LA QUE FALTABA)
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('whatsapp_creditos')->default('0');
            $table->string('hora_inicio')->nullable();
            $table->string('hora_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
