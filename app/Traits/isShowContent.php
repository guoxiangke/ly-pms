<?php

namespace App\Traits;

use App\Models\Content;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait isShowContent
{
    public function isShowContent(): Attribute {
        return Attribute::make(
            get: fn () => in_array($this->code, [
                'mw',
                'tmw',
                'hmw',
                'cmw',
                'it',
                'gw',
                'aw',
                'vp',
                'dr',
                'pk',
                'bs',
                'be',
            ]),
        );
    }
}