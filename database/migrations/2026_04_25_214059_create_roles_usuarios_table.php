<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles_usuarios', function (Blueprint $table) {
            $table->id();
            // Vinculamos con la tabla users (Laravel asume que es id en tabla users)
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            // Vinculamos con la tabla roles
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_usuarios');
    }
};
