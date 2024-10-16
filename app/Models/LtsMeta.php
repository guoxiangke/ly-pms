<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use Plank\Metable\Metable;
use Laravel\Scout\Searchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LtsMeta extends Model
{
    use HasFactory;
    use HasTags;
    use Metable;
    use SoftDeletes;
    // use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'begin_at', 'made_at'];
    
    protected $casts = [
        'begin_at' => 'date',
        'made_at' => 'date',
    ];

    protected $appends = [
        'cover',
        'category',
    ];

    public function getCategoryAttribute(){
        return $this->tags()->firstOrNew(['name'=>'noTagName'])->name;
    }
    
    public function getCoverAttribute(){
        $code = 'ltsnp';
        $domain =  config('pms.cloudfront_domain');
        $cover = "{$domain}/ly/image/cover/{$code}.jpg";

        return isset($this->avatar) ? Storage::url($this->avatar) : $cover;
    }

    public function lts_items(): HasMany
    {
        return $this->HasMany(LtsItem::class)->orderBy('alias', 'DESC');
    }

    public function lts_items_asc(): HasMany
    {
        return $this->HasMany(LtsItem::class)->orderBy('alias', 'ASC');
    }

    // FE显示，bu包含 明后天的及31天以外的节目
    public function lts_items_without_future(): HasMany
    {
        return $this->HasMany(LtsItem::class)
            ->whereBetween('play_at', [now()->subDays(31), now()])
            ->orderBy('alias', 'DESC');
    }

    // ly_meta_id 最近一次上架 分类：进深、本科等
    public function ly_meta(): BelongsTo
    {
        return $this->BelongsTo(LyMeta::class);
    }

    // 定义 belongsTo 关系到 LyMeta
    public function lyMeta()
    {
        return $this->belongsTo(LyMeta::class);
    }

    // 定义 hasMany 关系到 LtsItem
    public function ltsItems()
    {
        return $this->hasMany(LtsItem::class);
    }

    public function isLts(): Attribute
    {
        return Attribute::make(
            get: fn () => true,
        );
    }

}
