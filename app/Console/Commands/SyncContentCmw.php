<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\LyItem;
use Illuminate\Support\Facades\Log;


class SyncContentCmw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-content-cmw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从同步cmw旷野吗哪粤语和国语（日期对应）';
    /**
     * Execute the console command.
     */

    public function handle()
    {
        //粵語版曠野嗎哪，2025年2月1日的一集，等於普通話版曠野嗎哪2020年2月16日的内容，之後就是依照排下去。
        $fromDate = Carbon::create(2020, 2, 16)->timezone('Asia/Shanghai');
        $lyItems = LyItem::where('alias', 'like', 'cmw%')->orderBy('play_at', 'asc')->get();
        $count = 0;
        foreach ($lyItems as $lyItem) {
            if($lyItem->contents()->count()) continue;//已经有内容的跳过
            $alias = 'mw'. $fromDate->copy()->addDays($count++)->format('ymd');
            Log::info( $alias . ' => ' . $lyItem->alias);
            $mwLyItem = LyItem::where('alias', $alias)->first();
            $content = $mwLyItem->contents()->first();
            if($content) $lyItem->contents()->attach($content->id);
        }
    }
}
