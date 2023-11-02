<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

// BibleItem
class Scripture extends Model
{
    use HasFactory;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;
}
