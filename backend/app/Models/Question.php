<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mission_stage_id', 'subject_id', 'skill_id', 'grade_id', 'type', 'statement',
        'explanation', 'difficulty', 'max_score', 'time_limit_seconds', 'status', 'author_id',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(MissionStage::class, 'mission_stage_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function correctOption(): HasOne
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }

    public function location(): HasOne
    {
        return $this->hasOne(QuestionLocation::class);
    }

    public function hints(): HasMany
    {
        return $this->hasMany(Hint::class)->orderBy('order');
    }
}
