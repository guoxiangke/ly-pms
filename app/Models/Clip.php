<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clip extends Model
{
    use HasFactory;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;

    public function lyItems(): MorphToMany
    {
        return $this->morphedByMany(LyItem::class, 'contentable');
    }

    public function ltsItems(): MorphToMany
    {
        return $this->morphedByMany(LtsItem::class, 'contentable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
