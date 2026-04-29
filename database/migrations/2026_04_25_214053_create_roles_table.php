<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->timestamps();
        });

        // Insertamos los roles por defecto
        DB::table('roles')->insert([
            ['id' => 1, 'titulo' => 'Administrador', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'titulo' => 'Dueño', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
