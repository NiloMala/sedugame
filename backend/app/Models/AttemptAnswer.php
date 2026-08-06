<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'answer_text', 'selected_option_id', 'latitude',
        'longitude', 'distance_meters', 'is_correct', 'score', 'time_spent_seconds', 'hints_used',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_meters' => 'decimal:2',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
