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
        Schema::create('planes_stripe', function (Blueprint $table) {
            $table->id();
            $table->integer('id_plan')->unique(); // Para amarrarlo a tu plan 1, 2, 3
            $table->string('titulo'); // Básico, Medio, Avanzado
            $table->string('stripe_price_id'); // El token real
            $table->decimal('precio_mensual', 10, 2); // Para tener la referencia local
            $table->timestamps();
        });

        //datos
        \Illuminate\Support\Facades\DB::table('planes_stripe')->insert([
            ['id_plan' => 1, 'titulo' => 'Básico', 'stripe_price_id' => 'price_1T8vySCPQ2Qy65AdXblJIcCw', 'precio_mensual' => 100.00],
            ['id_plan' => 2, 'titulo' => 'Medio', 'stripe_price_id' => 'price_1T8vyrCPQ2Qy65AdxgyLrYm3', 'precio_mensual' => 200.00],
            ['id_plan' => 3, 'titulo' => 'Avanzado', 'stripe_price_id' => 'price_1T8vz5CPQ2Qy65AdLIyKXr8M', 'precio_mensual' => 500.00],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_stripe');
    }
};
