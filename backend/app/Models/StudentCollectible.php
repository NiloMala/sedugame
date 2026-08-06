<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCollectible extends Model
{
    protected $fillable = ['student_id', 'collectible_item_id', 'unlocked_at'];

    protected function casts(): array
    {
        return ['unlocked_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CollectibleItem::class, 'collectible_item_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
