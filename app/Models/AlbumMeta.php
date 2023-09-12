<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use use \Spatie\Tags\HasTags;
use Laravel\Scout\Searchable;

class AlbumMeta extends Model
{
    use HasFactory;
    use HasTags;
    use Searchable;
}
