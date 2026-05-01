<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;

Route::get('/test-ai', [AiController::class, 'showTestView']);
Route::post('/ai/transcribe', [AiController::class, 'transcribe']);
Route::post('/ai/analyze-pdf', [AiController::class, 'analyzePdf']);
Route::post('/ai/similarity', [AiController::class, 'checkSimilarity']);