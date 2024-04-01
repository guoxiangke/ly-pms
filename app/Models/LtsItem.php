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
    // use Searchable;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
    protected $casts = [
        'play_at' => 'date',
    ];
    protected $appends = [
        'path',
    ];
    public function lts_meta(): BelongsTo
    {
        return $this->BelongsTo(LtsMeta::class);
    }

    public function getPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3 && $this->updated_at->diffInHours() < 24){
            return $this->mp3;
        }
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        return "/storage/ly/audio/{$code}/{$alias}.mp3"; 
    }

    // For nova add /storage/
    public function getNovaPathAttribute(){
        // 纠正的mp3 临时播放链接：ly/corrections/ynf230915-1-20231019_03:21:01-14125824-mw231008.mp3
        if($this->mp3 && $this->updated_at->diffInHours() < 24){
            return $this->mp3;
        }
        $code = preg_replace('/\d+/', '', $this->alias);
        $alias = $this->alias;
        return "/ly/audio/{$code}/{$alias}.mp3"; 
    }
}
