<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    protected $fillable = [
        'title', 'audio_path', 'transcription', 'summary', 'analysis_data', 'learning_module_id'
    ];

    protected $casts = [
        'analysis_data' => 'array'
    ];

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
}
