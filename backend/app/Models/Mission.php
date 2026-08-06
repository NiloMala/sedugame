<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    protected $fillable = [
        'campaign_id', 'title', 'slug', 'description', 'narrative', 'objective',
        'order', 'difficulty', 'estimated_minutes', 'max_score', 'status', 'unlock_rule',
    ];

    protected function casts(): array
    {
        return ['unlock_rule' => 'array'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(MissionStage::class)->orderBy('order');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'mission_media')->withPivot('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
