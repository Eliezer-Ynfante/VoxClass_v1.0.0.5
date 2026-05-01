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
    Schema::create('class_sessions', function (Blueprint $table) {
        $table->id();
        $table->string('title'); // Ejemplo: Clase de Historia - 20/10
        $table->string('audio_path')->nullable(); // Ruta del archivo .mp3
        $table->text('transcription')->nullable(); // El texto crudo de la IA
        $table->text('summary')->nullable(); // El resumen procesado
        $table->json('analysis_data')->nullable(); // Para métricas extra (sentimiento, palabras clave)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
