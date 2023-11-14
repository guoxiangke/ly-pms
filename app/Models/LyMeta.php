<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Metable\Metable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Tags\HasTags;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use App;

class LyMeta extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Metable;
    use HasTags;
    public static function getTagClassName(): string
    {
        return Tag::class;
    }
    // if(App::isProduction()) use Searchable;
    // use LogsActivity;
    protected static $recordEvents = ['updated','deleted'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ;
    }

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'begin_at', 'end_at', 'unpublished_at'];
    // https://laracasts.com/discuss/channels/nova/nova-datetime-field-must-cast-to-datetime-in-eloquent-model
    protected $casts = [
        'begin_at' => 'date',
        'end_at' => 'date',
        'unpublished_at' => 'date',
        'lts_first_play_at' => 'date',
    ];
    protected $appends = [
        'cover',
        'category', 
    ];
    

    public function announcers()
    {
        return $this->belongsToMany(Announcer::class, 'announcer_has_programs' , 'ly_meta_id', 'announcer_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }


    public function scopeActive($query)
    {
        return $query->whereNull('unpublish_at');
    }

    
    // append programs API的category
    // +category : "生活智慧"
    public function getCategoryAttribute(){
        return $this->tags()->firstOrNew(['name'=>'noTagName'])->name;
    }

    /**
     * Get the cover.
     */
    protected function cover(): Attribute
    {
        $code = substr($this->code, 2);
        return Attribute::make(
            get: fn () => isset($this->avatar) ? Storage::url($this->avatar) : "https://txly2.net/images/program_banners/{$code}_prog_banner_sq.png",
        );
    }

    // 前台显示，不包含 明后天的节目 @see LyItem::addGlobalScope('ancient'
    public function ly_items(): HasMany
    {
        return $this->HasMany(LyItem::class)
            ->whereBetween('play_at', [now()->subDays(31), now()])
            ->orderBy('alias', 'DESC');
    }
    
    // Call to undefined method App\Models\LyMeta::lyItems()
    public function lyitems(): HasMany
    {
        return $this->ly_items();
    }

    // 后台显示，包含 明后天的及31天以外的节目
    public function ly_items_with_future(): HasMany
    {
        return $this->HasMany(LyItem::class)
            ->withoutGlobalScopes()
            ->orderBy('alias', 'DESC');
    }
    
    public function getLtsFirstPlayAttribute(){
        return $this->getMeta('lts_first_play');
    }

    public function getLtsFirstPlayAtAttribute(){
        return \DateTime::createFromFormat("Y-m-d", $this->getMeta('lts_first_play_at','2000-01-01'));
    }

}
