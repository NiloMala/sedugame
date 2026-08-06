<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name', 'description', 'latitude', 'longitude', 'city', 'state', 'country',
        'biome', 'historical_period', 'source_type', 'source_url', 'license', 'attribution',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
