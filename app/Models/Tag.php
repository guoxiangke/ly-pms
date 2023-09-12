<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\Tag as SpatieTag;

class Tag extends SpatieTag
{
    protected $appends = ['ly_metas'];
    
    public function getLyMetasAttribute(){
        return LyMeta::withAnyTags($this->name, 'ly')->get();
    }
}
