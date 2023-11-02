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
use App;
use Illuminate\Database\Eloquent\SoftDeletes;

class LtsMeta extends Model
{
    use HasFactory;
    use HasTags;
    use Metable;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'begin_at', 'stop_at'];
    

    protected $appends = [
        'cover',
        'category',
    ];

    public function getCategoryAttribute(){
        return $this->tags()->firstOrNew(['name'=>'noTagName'])->name;
    }
    
    public function getCoverAttribute(){
        return isset($this->avatar) ? Storage::url($this->avatar) : "https://txly2.net/images/program_banners/ltsnp_prog_banner_sq.png";
    }

    // /**
    //  * Get the code.
    //  */
    // protected function code(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => 'ma' . $value,
    //         set: fn ($value) => substr($value, 2),
    //     );
    // }

    public function lts_items(): HasMany
    {
        return $this->HasMany(LtsItem::class)->orderBy('alias');
    }



}
