<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    protected $table = 'student_progress';

    protected $fillable = [
        'student_id', 'campaign_id', 'mission_id', 'progress_percent',
        'best_score', 'attempts_count', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
