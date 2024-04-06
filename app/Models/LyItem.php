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
        // 'is_old', // 旧系统的节目 目录结构
        'is_future', // 明后天的节目
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

    public function getIsOldAttribute()
    {
        // production Lanched Date
        return $this->play_at <  Carbon::createFromFormat('Y-m-d', config('pms.launched_at'));
    }


    public function getIsFutureAttribute()
    {
        return $this->play_at > now();
    }

    public function getPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3){
            if($this->updated_at->diffInHours() < 24) return $this->mp3;
        }
        // OLD: /ly/audio/2023/ttb/ttb230726.mp3
        // New: /ly/audio/ttb/2023/ttb230726.mp3
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        $year = $this->play_at->format('Y');
        $domain = config('app.url');
        if($this->is_old){
            return $domain . "/storage/ly/audio/{$year}/{$code}/{$alias}.mp3"; 
        }else{
            return $domain . "/storage/ly/audio/{$code}/{$year}/{$alias}.mp3"; 
        }
    }

    public function getNovaMp3PathAttribute(){
        $domain = config('app.url');
        return str_replace($domain.'/storage', '', $this->path);
    }
    // read only attribute.
    public function getEpisodeTitleAttribute(){
        return $this->ly_meta->name . "-" . $this->play_at->format("ymd");
    }
}
