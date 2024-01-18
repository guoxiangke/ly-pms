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
            $lastAlias = substr($lastLyItem->alias, 2);
            $lastItem = Item::where('alias',$lastAlias)->first();
            $itemCollections = Item::where('id', '>', $lastItem->id);
        }
        // $itemCollections = Item::where('id', '>', 0);
        $itemCollections->chunkById(2000, function (Collection $items)  {
            foreach ($items as $item) {
                if(Str::startsWith($item->alias, 'vsp'))  $item->alias = 'ma' . $item->alias;
                if(Str::startsWith($item->alias, 'ma')){
                    $code = substr($item->alias, 0, strlen($item->alias)-2); //vhx1 vpd0
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
                    $code = 'ma' . preg_replace('/\d+/','',$item->alias);
                    $lyMeta = LyMeta::firstOrCreate(['code'=> $code], ['name'=>'CBI_' . $code ,'unpublished_at'=>now()]);
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
