<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use Plank\Metable\Metable;

class LtsMeta extends Model
{
    use HasFactory;
    use HasTags;
    use Metable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'stop_at'];
    
}
