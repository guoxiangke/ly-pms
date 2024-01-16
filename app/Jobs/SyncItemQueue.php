<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Open\Item;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\LtsMeta;
use App\Models\LtsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncItemQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastLyItem = LyItem::withoutGlobalScopes()->latest()->first();
        if(!$lastLyItem){
            $itemCollections = Item::where('id', '>', 0);
        }else{
            $lastAlias = substr($lastLyItem->alias, 2);
            $lastItem = Item::where('alias',$lastAlias)->first();
            $itemCollections = Item::where('id', '>', $lastItem->id);
        }
        $itemCollections->chunk(1000, function (Collection $items)  {
            foreach ($items as $item) {
                if(Str::startsWith($item->alias, 'vsp'))  $item->alias = 'ma' . $item->alias;
                if(Str::startsWith($item->alias, 'ma')){
                    $code = substr($item->alias, 0, strlen($item->alias)-2); //vhx1 vpd0
                    $ltsMeta = LtsMeta::firstOrCreate(['code'=>$code], ['name'=>'created by import ' . $code ,'count'=>0]);
                    $newItem = LtsItem::updateOrCreate(['alias' => $item->alias], [
                        'lts_meta_id'=>$ltsMeta->id,
                        'play_at'=>$item->play_at,
                        'description'=>$item->description
                    ]);
                    Log::debug(__CLASS__, ['LtsItem', $item->id, $newItem->id, $item->alias, $code]);
                }else{
                    // cc230925
                    $code = 'ma' . preg_replace('/\d+/','',$item->alias);
                    $lyMeta = LyMeta::firstOrCreate(['code'=> $code], ['name'=>'created by import','unpublished_at'=>now()]);
                    $newItem = LyItem::withoutGlobalScopes()->updateOrCreate(['alias' => 'ma' . $item->alias], [
                        'ly_meta_id'=>$lyMeta->id,
                        // 'play_at'=>$item->play_at,
                        'description'=>$item->description
                    ]);
                    Log::debug(__CLASS__, ['LyItem', $item->id, $newItem->alias, $newItem->id, $newItem->wasRecentlyCreated]);
                }
            }
        });

    }
}
