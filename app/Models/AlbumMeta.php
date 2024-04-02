<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlbumMeta extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTags;
    // if(App::isProduction()) use Searchable;
}
