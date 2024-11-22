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
    // // 定义多态的正向多对多关联 如：Tag::class
    // public function contents(): MorphToMany
    // {
    //     return $this->morphToMany(Content::class, 'contentable');
    // }

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function lyItems(): MorphToMany
    {
        return $this->morphedByMany(LyItem::class, 'attachmentable');
    }

    public function ltsItems(): MorphToMany
    {
        return $this->morphedByMany(LtsItem::class, 'attachmentable');
    }
    
    public function content(): MorphToMany
    {
        return $this->morphedByMany(LtsItem::class, 'attachmentable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
