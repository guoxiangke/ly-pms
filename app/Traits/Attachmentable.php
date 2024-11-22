<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Attachmentable
{
    // 如：
        // Post hasMany Tags
        // Article hasMany Tags
        // Tags hasMany Post and Article
    // 定义多态的正向多对多关联
    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachmentable');
    }
}