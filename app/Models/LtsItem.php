<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App;
use Laravel\Scout\Searchable;
use Deligoez\LaravelModelHashId\Traits\HasHashId;

class LtsItem extends Model
{
    use HasHashId;
    use HasFactory;
    use SoftDeletes;
    protected $with = ['ltsMeta'];
    // use Searchable;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
    protected $casts = [
        'play_at' => 'date',
    ];
    protected $appends = [
        'path',
    ];
    // public function getPlayAtAttribute($value){
    //     if(!$value) return now();
    // }
    public function lts_meta(): BelongsTo
    {
        return $this->BelongsTo(LtsMeta::class)->withTrashed();
    }

    // 定义 belongsTo 关系到 LtsMeta
    public function ltsMeta()
    {
        return $this->belongsTo(LtsMeta::class)->withTrashed();
    }

    public function getPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3 && $this->updated_at->diffInHours() < 24){
            return $this->mp3;
        }
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        $domain = config('app.url');
        return $domain . "/storage/ly/audio/{$code}/{$alias}.mp3"; 
    }

    // For nova add /storage/
    public function getNovaMp3PathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3 && $this->updated_at->diffInHours() < 24){
            return $this->mp3;
        }
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        return "/ly/audio/{$code}/{$alias}.mp3"; 
    }

    // read only attribute.
    public function getEpisodeTitleAttribute(){
        return $this->ltsMeta->name . "-" . filter_var($this->ltsMeta->code, FILTER_SANITIZE_NUMBER_INT) . str_replace($this->ltsMeta->code, '', $this->alias);
    }
}
