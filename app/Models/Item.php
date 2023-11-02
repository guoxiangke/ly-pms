<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Plank\Metable\Metable;
// use Spatie\Activitylog\Traits\LogsActivity;
// use Laravel\Scout\Searchable;

class Item extends Model
{
    protected $connection = 'mysql_open_import';

	protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'play_at'];
	
    use HasFactory;
	use SoftDeletes;
    public function getDate()
    {
        if($this->play_at){
            $playAt = $this->play_at->format('ymd');
        }else{
            preg_match('/(\D+)(\d+)/', $this->alias, $matchs); //mavbm
            $playAt = $matchs[2];
        }
        return $playAt;
    }
}
