<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptHint extends Model
{
    public $timestamps = false;

    protected $fillable = ['attempt_id', 'question_id', 'hint_id', 'used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function hint(): BelongsTo
    {
        return $this->belongsTo(Hint::class);
    }
}
