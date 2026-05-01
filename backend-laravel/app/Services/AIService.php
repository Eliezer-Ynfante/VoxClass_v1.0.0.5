<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "http://ia-service:8000";
    }

    public function transcribe($audio)
    {
        $response = Http::attach(
            'file', 
            file_get_contents($audio->getRealPath()), 
            $audio->getClientOriginalName()
        )->post("{$this->baseUrl}/transcribe-audio");

        return $response->json();
    }

    public function analyzePdf($pdf)
    {
        $response = Http::attach(
            'file', 
            file_get_contents($pdf->getRealPath()), 
            $pdf->getClientOriginalName()
        )->post("{$this->baseUrl}/analyze-pdf");

        return $response->json();
    }

    public function checkSimilarity($text1, $text2)
    {
        $response = Http::post("{$this->baseUrl}/similarity", [
            'text1' => $text1,
            'text2' => $text2,
        ]);

        return $response->json();
    }
}