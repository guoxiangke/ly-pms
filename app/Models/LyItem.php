<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Deligoez\LaravelModelHashId\Traits\HasHashId;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LyItem extends Model implements HasMedia
{
    use HasHashId;
    use InteractsWithMedia;
    use HasFactory;
    use SoftDeletes;
    // use Searchable;
    // use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
    protected $casts = [
        'play_at' => 'date',
    ];
    protected $appends = [
        'path',
        'is_published', // 明后天的节目
        'episode_title', // Episode Title
    ];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            // @see LyItemObserver
            ->dontLogIfAttributesChangedOnly(['play_at']);
    }
    public function content(): MorphOne
    {
        return $this->morphOne(Content::class, 'contentable');
    }

    public function marks(): MorphMany
    {
        return $this->morphMany(Mark::class, 'markable');
    }

    public function ly_meta(): BelongsTo
    {
        return $this->BelongsTo(LyMeta::class);
    }

    public function announcer(): BelongsTo
    {
        return $this->BelongsTo(Announcer::class);
    }

    public function getIsFutureAttribute()
    {
        return $this->play_at > now();
    }

    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->play_at < now(),
        );
    }

    public function getPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        $domain = config('app.url');
        if($this->mp3 && $this->updated_at->diffInHours() < 24) return $domain .'/'. $this->mp3;
        // No old and no new.
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        $year = $this->play_at->format('Y');
        return $domain . "/storage/ly/audio/{$year}/{$code}/{$alias}.mp3";
    }

    public function getNovaMp3PathAttribute(){
        $domain = config('app.url');
        if($this->mp3 && $this->updated_at->diffInHours() < 24) return '/' . $this->mp3;
        return str_replace($domain.'/storage', '', $this->path);
    }
    // read only attribute.
    public function getEpisodeTitleAttribute(){
        return $this->id?$this->ly_meta->name . "-" . $this->play_at->format("Ymd"):'-';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        return $this->isPublished();
    }

    public function toSearchableArray()
    {
        return ['id' => (string) $this->id] + $this->toArray();
    }
}
