<?php

namespace App\Models\Open;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $connection = 'mysql_open_import';

	protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
}
