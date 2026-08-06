<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'registration_number', 'school_id', 'class_id', 'birth_date',
        'status', 'experience', 'streak_days',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function level(): ?Level
    {
        return Level::forExperience($this->experience);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }
}
