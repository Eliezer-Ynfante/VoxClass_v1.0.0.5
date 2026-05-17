<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function showTestView()
    {
        return view('test-ai');
    }

    public function showIntegratedView() 
    {
    return view('integrated-session');
    }

    public function showLiveSessionView() 
    {
        $modules = \App\Models\LearningModule::orderBy('created_at', 'desc')->get();
        return view('live-session', compact('modules'));
    }

    public function transcribe(Request $request)
    {
        $result = $this->aiService->transcribe($request->file('audio'));
        return response()->json($result);
    }

    public function analyzePdf(Request $request)
    {
        $result = $this->aiService->analyzePdf($request->file('pdf'));
        return response()->json($result);
    }

    public function checkSimilarity(Request $request)
    {
        $request->validate([
            'text1' => 'required|string',
            'text2' => 'required|string'
        ]);
        $result = $this->aiService->checkSimilarity($request->text1, $request->text2);
        return response()->json($result);
    }
}