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
        Schema::create('tipos_numero_telefono', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_numero_telefono');
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('tipos_numero_telefono')->insert([
            ['id' => 1, 'tipo_numero_telefono' => 'WhatsApp', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tipo_numero_telefono' => 'Fijo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'tipo_numero_telefono' => 'Telegram', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_numero_telefono');
    }
};
