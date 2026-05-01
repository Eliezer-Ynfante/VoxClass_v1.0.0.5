<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AnalyzePdfJob implements ShouldQueue
{
    use Queueable;

    public $moduleId;
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($moduleId, $filePath)
    {
        $this->moduleId = $moduleId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $fileBit = Storage::disk('public')->get($this->filePath);

            // Intentar conectar con la IA
            $response = Http::attach('file', $fileBit, 'clase.pdf')
                            ->post('http://ia-service:8000/analyze-pdf');

            if ($response->successful()) {
                $result = $response->json();

                DB::table('learning_modules')->where('id', $this->moduleId)->update([
                    'title' => $result['title'],
                    'expected_content' => $result['full_text'],
                    'keywords' => json_encode($result['keywords']),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('learning_modules')->where('id', $this->moduleId)->update([
                    'title' => 'Error: IA respondió con error',
                ]);
            }
        } catch (\Exception $e) {
            DB::table('learning_modules')->where('id', $this->moduleId)->update([
                'title' => 'Fallo de conexión con la IA',
            ]);
        }
    }
}
