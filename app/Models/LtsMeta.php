<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use Plank\Metable\Metable;
use Laravel\Scout\Searchable;
use Illuminate\Support\Facades\Storage;

class LtsMeta extends Model
{
    use HasFactory;
    use HasTags;
    use Metable;
    use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'stop_at'];
    
    public function getCoverAttribute(){
        return isset($this->avatar) ? Storage::url($this->avatar) : "https://txly2.net/images/program_banners/ltsnp_prog_banner_sq.png";
    }
}
