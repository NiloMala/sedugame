<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Achievement extends Model
{
    use SoftDeletes;

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
