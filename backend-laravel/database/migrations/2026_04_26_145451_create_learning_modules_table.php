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
        Schema::create('learning_modules', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // Lo extraerá la IA
            $table->text('expected_content')->nullable(); // El texto crudo del PDF
            $table->json('keywords')->nullable(); // Los conceptos clave para el streaming
            $table->string('file_path'); // Donde guardamos el PDF físicamente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_modules');
    }
};
