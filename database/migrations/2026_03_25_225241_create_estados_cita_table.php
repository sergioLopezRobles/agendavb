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
        Schema::create('estados_cita', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->timestamps();
        });

        DB::table('estados_cita')->insert([
            [
                'titulo' => 'Creada',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'titulo' => 'En Proceso',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'titulo' => 'Terminada',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'titulo' => 'Cancelada',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_cita');
    }
};
