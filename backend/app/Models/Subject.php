<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'icon', 'color', 'status'];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
