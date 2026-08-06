<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hint extends Model
{
    protected $fillable = ['question_id', 'type', 'content', 'score_penalty', 'order'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
