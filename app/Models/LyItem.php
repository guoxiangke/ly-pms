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
use App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LyItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
    protected $casts = [
        'play_at' => 'date',
    ];
    protected $appends = [
        'path',
        // 'is_old', // 旧系统的节目 目录结构
        'is_future', // 明后天的节目
    ];
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
        // TODO change date for is_old in getIsOldAttribute() of Models/LyItem
        $ymd = config('app.live_at', "2024-01-01");
        $productionDate = Carbon::createFromFormat('Y-m-d', $ymd);
        return $this->play_at < $productionDate;
    }


    public function getIsFutureAttribute()
    {
        return $this->play_at > now();
    }

    public function getPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3 && $this->updated_at->diffInHours() < 24){
            return $this->mp3;
        }
        // OLD: /ly/audio/2023/ttb/ttb230726.mp3
        // New: /ly/audio/ttb/2023/ttb230726.mp3
        // RAW: /ly/audio/ raw /ttb/2023/ttb230726.mp3
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        if($this->is_old) {
            $code = substr($code, 2);
            $alias = substr($alias, 2);
        }
        $year = $this->play_at->format('Y');
        if($this->is_old){
            return "/ly/audio/{$year}/{$code}/{$alias}.mp3"; 
        }else{
            return "/ly/audio/{$code}/{$year}/{$alias}.mp3"; 
        }
    }
    
    protected static function booted()
    {
        //√ hide if get 230930 when in 230926 in query. 
        // 1.默认显示比当前日期小的节目。
        // 2.不超过30听的数据 for 未登录的用户
        //√ 404 if get mp3! @see routes/web.php 
        static::addGlobalScope('ancient', function (Builder $builder) {
            if(is_null(Auth::user())){
                //TODO Var 31 config("ly.max.show.days")=31
                $builder->whereBetween('play_at', [now()->subDays(31), now()]);
            }else{
                $builder->where('play_at', '<=', now());
            }
        });

        
        static::addGlobalScope('ancient', function (Builder $builder) {
            $builder->where('play_at', '<=', now());
        });
    }

}
