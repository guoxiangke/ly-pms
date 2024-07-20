<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\LyItem;
use App\Models\LyMeta;

class LyPulse extends Component
{

    public function render()
    {   
        $dayOfWeek = now()->dayOfWeek;
        // $dayOfWeek = 7;
        if($dayOfWeek != 1){
            $before = $dayOfWeek-1;
            $after = 21-$before;
        }else{
            $before = 0;
            $after = 21;
        }
        // 这个需求很复杂！
        $lyMetas = LyMeta::active()->notLts()->orderBy('code')->get();
        $lyItems = LyItem::whereBetween('play_at',[now()->subDay($before)->startOfDay(),now()->addDays($after)->startOfDay()])->get()->groupBy('ly_meta_id');
        return view('livewire.ly-pulse',compact('lyMetas', 'lyItems', 'before', 'after'))
            ->layout('layouts.pulse');
    }
}
