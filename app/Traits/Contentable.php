<?php

namespace App\Traits;

use App\Models\Content;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Contentable
{
    // 定义多态的正向多对多关联 如：Tag::class
    public function contents(): MorphToMany
    {
        return $this->morphToMany(Content::class, 'contentable');
    }
}