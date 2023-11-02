<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibleMeta extends Model
{
    use HasFactory;
    use SoftDeletes;
    // if(App::isProduction()) use Searchable;
    // https://wd.bible/bible/books/cunps
    // https://wd.bible/bible/chapterhtml/cunps/psa.27
    // https://wd.bible/highlight/get/cunps/psa.27
    // https://wd.bible/note/get/cunps/psa.27
}
