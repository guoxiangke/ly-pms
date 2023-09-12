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
    use Searchable;

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
    protected $appends = [
        'api_url',
        'category', 
    ];
    
    // https://laracasts.com/discuss/channels/nova/nova-datetime-field-must-cast-to-datetime-in-eloquent-model
    protected $casts = [
        'begin_at' => 'date',
        'stop_at' => 'date',
    ];

    public function announcers()
    {
        return $this->belongsToMany(Announcer::class, 'announcer_has_programs' , 'ly_meta_id', 'announcer_id');
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(Maker::class);
    }


    public function scopeActive($query)
    {
        return $query->whereNull('stop_at');
    }

    // append programs API的api_url
    // +api_url : /api/programs/cc
    public function getApiUrlAttribute(){
        return config('app.url') . '/api/program/'. $this->code;
    }
    // append programs API的category
    // +category : "生活智慧"
    public function getCategoryAttribute(){
        return $this->tags()->first()->name;
    }
    
    // protected static function booted()
    // {
    //     static::addGlobalScope('online', function (Builder $builder) {
    //         $builder->whereNotIn('code', ['bsm', 'kbk', 'ugn', 'lisu', 'mgg']);
    //     });
    // }

    /**
     * Get the cover.
     */
    protected function cover(): Attribute
    {
        return Attribute::make(
            get: fn () => isset($this->avatar) ? Storage::url($this->avatar) : "https://txly2.net/images/program_banners/{$this->code}_prog_banner_sq.png",
        );
    }

}
