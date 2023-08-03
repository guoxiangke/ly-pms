<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use use \Spatie\Tags\HasTags;

class AlbumMeta extends Model
{
    use HasFactory;
    use HasTags;
}
