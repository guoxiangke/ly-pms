<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Contentable;

class Attachment extends Model
{
    use HasFactory;
    use SoftDeletes;

    use Contentable;
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function lyItems(): MorphToMany
    {
        return $this->morphedByMany(LyItem::class, 'attachmentable');
    }

    public function ltsItems(): MorphToMany
    {
        return $this->morphedByMany(LtsItem::class, 'attachmentable');
    }

    public function contents()
    {
        return $this->morphedByMany(Content::class, 'attachmentable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
