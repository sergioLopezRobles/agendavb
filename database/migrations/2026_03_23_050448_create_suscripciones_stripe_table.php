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
        Schema::create('suscripciones_stripe', function (Blueprint $table) {
            $table->id();
            $table->integer('id_usuario');
            $table->string('nombre'); // Ej: 'basico', 'medio', 'avanzado'
            $table->string('stripe_id')->unique(); // El ID de la suscripción sub_xxx
            $table->string('stripe_status'); // active, past_due, canceled
            $table->string('stripe_price')->nullable(); // price_xxx
            $table->integer('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones_stripe');
    }
};
