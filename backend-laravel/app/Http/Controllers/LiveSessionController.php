<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\LearningModule;
use Illuminate\Http\Request;

class LiveSessionController extends Controller
{
    // POST /api/modules
    public function saveModule(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'expected_content' => 'required|string',
            'keywords' => 'required|array',
            'pdf_file' => 'required|file|mimes:pdf'
        ]);

        $path = $request->file('pdf_file')->store('pdfs', 'public');

        $module = LearningModule::create([
            'title' => $request->title,
            'expected_content' => $request->expected_content,
            'keywords' => $request->keywords,
            'file_path' => $path
        ]);

        return response()->json(['module_id' => $module->id]);
    }

    // POST /api/sessions/start
    public function startSession(Request $request)
    {
        $request->validate([
            'learning_module_id' => 'required|exists:learning_modules,id'
        ]);

        $session = ClassSession::create([
            'title' => 'Sesión en vivo - ' . now()->format('d/m/Y H:i'),
            'learning_module_id' => $request->learning_module_id,
            'transcription' => null,
            'analysis_data' => null
        ]);

        return response()->json(['session_id' => $session->id]);
    }

    // PUT /api/sessions/{id}/finalize
    public function finalizeSession(Request $request, $id)
    {
        $session = ClassSession::findOrFail($id);

        $request->validate([
            'transcription' => 'required|string',
            'similarity_score' => 'required|numeric',
            'interpretation' => 'required|string'
        ]);

        $analysis_data = [
            'similarity_score' => $request->similarity_score,
            'interpretation' => $request->interpretation
        ];

        $session->update([
            'transcription' => $request->transcription,
            'analysis_data' => $analysis_data
        ]);

        if ($request->hasFile('audio_file')) {
            $path = $request->file('audio_file')->store('audios', 'public');
            $session->update(['audio_path' => $path]);
        }

        return response()->json(['message' => 'Sesión consolidada']);
    }
}
