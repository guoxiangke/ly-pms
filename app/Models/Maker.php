<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maker extends Model
{
    use HasFactory;

    public function ly_metas(): HasMany
    {
        return $this->hasMany(LyMeta::class, 'maker_id');
    }
}
