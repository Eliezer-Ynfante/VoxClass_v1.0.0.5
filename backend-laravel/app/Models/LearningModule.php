<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    protected $fillable = [
        'title', 'expected_content', 'keywords', 'file_path'
    ];

    protected $casts = [
        'keywords' => 'array'
    ];
}
