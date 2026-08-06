<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionStage extends Model
{
    protected $fillable = ['mission_id', 'title', 'description', 'content', 'order', 'location_id'];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
