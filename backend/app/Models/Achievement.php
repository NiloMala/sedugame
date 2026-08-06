<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = [
        'title', 'description', 'icon', 'rule_type', 'rule_value',
        'experience_reward', 'status', 'reward_collectible_item_id',
    ];

    protected function casts(): array
    {
        return ['rule_value' => 'array'];
    }

    public function rewardCollectible(): BelongsTo
    {
        return $this->belongsTo(CollectibleItem::class, 'reward_collectible_item_id');
    }
}
