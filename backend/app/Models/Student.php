<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'registration_number', 'school_id', 'class_id', 'birth_date',
        'status', 'experience', 'streak_days', 'avatar_base', 'equipped_accessory_id',
        'last_activity_date',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'last_activity_date' => 'date'];
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

    public function collectibles(): HasMany
    {
        return $this->hasMany(StudentCollectible::class);
    }

    public function equippedAccessory(): BelongsTo
    {
        return $this->belongsTo(CollectibleItem::class, 'equipped_accessory_id');
    }

    /**
     * Atualiza streak_days com base em last_activity_date. Chamado ao
     * concluir uma tentativa (ProgressionService::applyCompletion).
     */
    public function registerActivityToday(): void
    {
        $today = now()->toDateString();

        if ($this->last_activity_date?->toDateString() === $today) {
            return; // já contou hoje
        }

        $yesterday = now()->subDay()->toDateString();
        $this->streak_days = $this->last_activity_date?->toDateString() === $yesterday
            ? $this->streak_days + 1
            : 1;

        $this->last_activity_date = $today;
        $this->save();
    }
}
