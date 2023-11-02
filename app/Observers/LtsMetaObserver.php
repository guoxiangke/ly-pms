<?php

namespace App\Observers;

use App\Models\LtsMeta;
use App\Models\LtsItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LtsMetaObserver
{
    /**
     * Handle the LtsMeta "created" event.
     */
    public function created(LtsMeta $ltsMeta): void
    {
        //vspsa546 => mavspsa501
        $count = $ltsMeta->count;
        while ($count--){
            $index = str_pad($ltsMeta->count-$count, 2, '0', STR_PAD_LEFT);
            LtsItem::firstOrCreate([
                'lts_meta_id'=> $ltsMeta->id,
                'alias'=> $ltsMeta->code . $index,// mavam101-mavam124
            ]);
        }
    }

    /**
     * Handle the LtsMeta "updated" event.
     */
    public function updated(LtsMeta $ltsMeta): void
    {
        $oldCount = $ltsMeta->getOriginal('count');//30
        $count = $ltsMeta->count;//24
        // 如果改数量 24=〉30
        if($ltsMeta->count-$oldCount>0){
            while ($count > $oldCount){
                $index = str_pad($count, 2, '0', STR_PAD_LEFT);
                $count--;
                $item = LtsItem::withTrashed()->firstWhere('alias', $ltsMeta->code . $index);
                if($item){
                    $item->restore();
                }else{
                    LtsItem::create([
                        'alias' => $ltsMeta->code . $index,
                        'lts_meta_id' => $ltsMeta->id,
                        'description' => 'created by increase count',
                    ]);
                }
            }
        }else{
            // 如果改数量 30 =〉24
            while ($count++ < $oldCount){ //25~30
                $index = str_pad($count, 2, '0', STR_PAD_LEFT);
                $item = LtsItem::firstWhere('alias', $ltsMeta->code . $index);
                if($item){
                    $item->delete();
                };
            }
        }
        // 如果改code 即 alias 版本  mavst0 =》 mavst1
        if($ltsMeta->isDirty('code')){
            $oldCode = $ltsMeta->getOriginal('code');
            $newCode = $ltsMeta->code;
            $ltsMeta->ltsItems()->each(function ($ltsItem) use($oldCode, $newCode){
                $ltsItem->alias = Str::replace($oldCode, $newCode, $ltsItem->alias);
                $ltsItem->save();
            });
        }
    }

    /**
     * Handle the LtsMeta "deleted" event.
     */
    public function deleted(LtsMeta $ltsMeta): void
    {
        //
    }

    /**
     * Handle the LtsMeta "restored" event.
     */
    public function restored(LtsMeta $ltsMeta): void
    {
        //
    }

    /**
     * Handle the LtsMeta "force deleted" event.
     */
    public function forceDeleted(LtsMeta $ltsMeta): void
    {
        //
    }
}
