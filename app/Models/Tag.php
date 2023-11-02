<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\Tag as SpatieTag;
use Plank\Metable\Metable;

class Tag extends SpatieTag
{
    // add meta desc to tags
    use Metable;
    protected $appends = ['ly_metas'];
    
    public function getLyMetasAttribute(){
        return LyMeta::withAnyTags($this->name, 'ly')->get();
    }
}
