<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'type', 'title', 'file_url', 'thumbnail_url', 'source', 'license',
        'author', 'attribution', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'mission_media')->withPivot('order');
    }
}
