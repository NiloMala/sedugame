<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'minimum_experience', 'maximum_experience', 'icon', 'order'];

    /**
     * Retorna o nível correspondente a uma quantidade de experiência.
     */
    public static function forExperience(int $experience): ?self
    {
        return static::where('minimum_experience', '<=', $experience)
            ->orderByDesc('minimum_experience')
            ->first();
    }
}
