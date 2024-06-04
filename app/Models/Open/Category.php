<?php

namespace App\Models\Open;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'mysql_open_import';

	protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
