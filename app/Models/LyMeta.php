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

class LyMeta extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Metable;
    use HasTags;

    use LogsActivity;
    protected static $recordEvents = ['updated','deleted'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ;
    }

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'begin_at', 'stop_at'];
    
    // https://laracasts.com/discuss/channels/nova/nova-datetime-field-must-cast-to-datetime-in-eloquent-model
    protected $casts = [
        'begin_at' => 'date',
        'stop_at' => 'date',
    ];

    public function announcers()
    {
        return $this->belongsToMany(Announcer::class, 'announcer_has_programs' , 'ly_meta_id', 'announcer_id');
    }

    public function maker()
    {
        return $this->belongsTo(Maker::class);
    }


    public function scopeActive($query)
    {
        return $query->whereNull('stop_at');
    }

    // protected static function booted()
    // {
    //     static::addGlobalScope('online', function (Builder $builder) {
    //         $builder->whereNotIn('code', ['bsm', 'kbk', 'ugn', 'lisu', 'mgg']);
    //     });
    // }

}
