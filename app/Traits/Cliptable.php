<?php

namespace App\Traits;

use App\Models\Content;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Cliptable
{
    public function clips(): MorphToMany
    {
        return $this->morphToMany(Content::class, 'cliptable');
    }
}