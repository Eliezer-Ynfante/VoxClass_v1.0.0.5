<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;
use App\Http\Controllers\LiveSessionController;

Route::get('/test-ai', [AiController::class, 'showTestView']);
Route::get('/integrated-session', [AiController::class, 'showIntegratedView']);
Route::get('/live-session', [AiController::class, 'showLiveSessionView']);

Route::post('/ai/transcribe', [AiController::class, 'transcribe']);
Route::post('/ai/analyze-pdf', [AiController::class, 'analyzePdf']);
Route::post('/ai/similarity', [AiController::class, 'checkSimilarity']);

Route::post('/modules', [LiveSessionController::class, 'saveModule']);
Route::post('/sessions/start', [LiveSessionController::class, 'startSession']);
Route::put('/sessions/{id}/finalize', [LiveSessionController::class, 'finalizeSession']);