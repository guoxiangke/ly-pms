<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcer extends Model
{
    use HasFactory;

    public function LyMetas(): BelongsToMany
    {
        return $this->belongsToMany(LyMeta::class, 'announcer_has_programs', 'announcer_id', 'ly_meta_id'); //
    }
}
