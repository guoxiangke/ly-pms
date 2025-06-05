<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\morphedByMany;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Attachmentable;
use Plank\Metable\Metable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Content extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use Attachmentable;//attachments()
    use InteractsWithMedia;
    use Metable;
    // $content->setMeta('joomla_article_id', 5);
    // $content = Content::whereMeta('joomla_article_id', 5);

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
