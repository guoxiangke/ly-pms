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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    // 定义虚拟属性的 getter
    public function getPdfFileAttribute()
    {
        $attachment = $this->attachments()->latest()->first();
        return $attachment ? $attachment->path : null;
    }
    
    // 定义虚拟属性的 setter（阻止实际保存）
    public function setPdfFileAttribute($value)
    {
        // 什么都不做，阻止保存到数据库
    }
}
