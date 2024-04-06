<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncItemQueue;

use App\Models\Open\Item;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\LtsMeta;
use App\Models\LtsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncOpen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-open';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from ly-open api system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // SyncItemQueue::dispatch();
        $lastLyItem = LyItem::withoutGlobalScopes()->latest()->first();
        if(!$lastLyItem){
            $itemCollections = Item::where('id', '>', 0);
        }else{
            $lastAlias = $lastLyItem->alias;
            $lastItem = Item::where('alias',$lastAlias)->firstOrFail();
            $itemCollections = Item::where('id', '>', $lastItem->id);
        }
        // $itemCollections = Item::where('id', '>', 0);
        $itemCollections->chunkById(2000, function (Collection $items)  {
            foreach ($items as $item) {
                // vspsa546 => mavspsa501
                if(Str::startsWith($item->alias, 'vsp'))  $item->alias = 'ma' . $item->alias;
                if(Str::startsWith($item->alias, 'ma')){
                    // vhx1 vpd0
                    $code = substr($item->alias, 0, strlen($item->alias)-2);
                    $ltsMeta = LtsMeta::firstOrCreate(['code'=>$code], [
                        'name'=>'CBI_' . $code ,
                        'count'=>0,
                    ]);
                    $newItem = LtsItem::updateOrCreate(['alias' => $item->alias], [
                        'lts_meta_id'=>$ltsMeta->id,
                        'play_at'=>$item->play_at,
                        'description'=>$item->description
                    ]);
                    Log::debug(__CLASS__, ['LtsItem', $item->id, $newItem->id, $item->alias, $code]);
                }else{
                    // cc230925
                    $code = preg_replace('/\d+/','',$item->alias);//cc
                    if(isset($specials[$code])){
                        // 'cwa'=>'cawa',
                        $alias = str_replace($code, $specials[$code], $item->alias);
                        $code = $specials[$code];
                    }else{
                        if(Str::startsWith($item->alias,'ca')){
                             //ca 开头的，不加ma,
                            $alias = $item->alias;// code 不变，$alias 也不变 = 原来的。
                        }else{
                            $code = $code; //macc
                            $alias = $item->alias;
                        }
                    }
                    $lyMeta = LyMeta::firstOrCreate(['code'=> $code], ['name'=>'CBI_' . $code ,'unpublished_at'=>now()]);
                    $newItem = LyItem::withoutGlobalScopes()->updateOrCreate(['alias' => $alias], [
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
