<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectibleItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'category', 'image_url', 'icon', 'rarity', 'status'];
}
