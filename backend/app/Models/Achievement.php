<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['title', 'description', 'icon', 'rule_type', 'rule_value', 'experience_reward', 'status'];

    protected function casts(): array
    {
        return ['rule_value' => 'array'];
    }
}
