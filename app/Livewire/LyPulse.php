<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\LyItem;
use App\Models\LyMeta;

class LyPulse extends Component
{

    public function render()
    {
        $before = 6;
        $after = 8;
        $lyMetas = LyMeta::active()->notLts()->get();
        $lyItems = LyItem::whereBetween('play_at',[now()->subDay($before)->startOfDay(),now()->addDays($after)->startOfDay()])->get()->groupBy('ly_meta_id');
        return view('livewire.ly-pulse',compact('lyMetas', 'lyItems', 'before', 'after'))
            ->layout('layouts.app');
    }
}
