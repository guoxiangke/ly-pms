<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\morphedByMany;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Attachmentable;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Attachmentable;//attachments()

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // if(App::isProduction()) use Searchable;

    public function lyItems()
    {
        return $this->morphedByMany(LyItem::class, 'contentable');
    }

    public function ltsItems()
    {
        return $this->morphedByMany(LtsItem::class, 'contentable');
    }

    // public function attachments()
    // {
    //     return $this->morphedByMany(Attachment::class, 'contentable');
    // }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
