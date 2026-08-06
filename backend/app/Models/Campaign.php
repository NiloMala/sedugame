<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'cover_image_url', 'primary_subject_id', 'grade_id',
        'difficulty', 'status', 'visibility', 'author_id', 'published_at',
        'estimated_minutes', 'max_score',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function primarySubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'primary_subject_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'campaign_subjects');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'campaign_skills');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class)->orderBy('order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
