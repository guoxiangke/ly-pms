<?php

namespace App\Traits;

use App\Models\Clip;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Cliptable
{
    public function clips(): MorphToMany
    {
        return $this->morphToMany(Clip::class, 'cliptable');
    }
}