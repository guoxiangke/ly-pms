<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcer extends Model
{
    use HasFactory;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'begin_at', 'stop_at'];
    
    public function lyMetas(): BelongsToMany
    {
        return $this->belongsToMany(LyMeta::class, 'announcer_has_programs', 'announcer_id', 'ly_meta_id'); //
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // $currentProgramCode = 'cc';
    // // 用户 对应一个 主持人
    // if($announcer = $user->announcer){
    //     // 主持人 对应一些节目
    //     if($announcer->lyMetas->pluck('code')->contains($currentProgramCode)){
    //         return true; // to upload
    //     }
    // }
}
