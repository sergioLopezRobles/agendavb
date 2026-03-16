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
        Schema::create('prioridad_ticket_soporte_usuarios_negocios', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });

        DB::table('prioridad_ticket_soporte_usuarios_negocios')->insert([
            [
                'descripcion' => 'Baja',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'descripcion' => 'Media',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'descripcion' => 'Alta',
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
        Schema::dropIfExists('prioridad_ticket_soporte_usuarios_negocios');
    }
};
