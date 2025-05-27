<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use League\HTMLToMarkdown\HtmlConverter;
use Carbon\Carbon;
use App\Models\LyItem;
use App\Models\LyMeta;
use Illuminate\Support\Facades\Log;
use App\Services\ContentSyncService;


class SyncContentDoubleCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-content-double-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从资源库double check同步的mw it内容';

    private $contentSyncService;
    public function __construct(ContentSyncService $contentSyncService)
    {
        parent::__construct();
        $this->contentSyncService = $contentSyncService;
    }
    public function handle()
    {
        $code = 'it';
        $lyMeta = LyMeta::where('code', $code)->first();
        foreach (LyItem::where('ly_meta_id', $lyMeta->id)->cursor() as $lyItem) {
            // 每次处理一条记录，不会一次性加载所有数据到内存
            if(0 == $lyItem->contents->count()){
                $alias = $lyItem->alias;
                $url = "https://r1.zyqstx.net/sermon/sermon-it/sermon-it-{$alias}";
                // https://r1.zyqstx.net/sermon/sermon-it/sermon-it-it210301
                // https://r1.zyqstx.net/sermon/sermon-it/sermon-it-it160512
                $this->contentSyncService->save($lyItem, $url, $alias, $lyMeta);
                Log::debug(__CLASS__,[$lyItem->id, $alias]);
            }
        }

        $code = 'mw';
        $lyMeta = LyMeta::where('code', $code)->first();
        foreach (LyItem::where('ly_meta_id', $lyMeta->id)->cursor() as $lyItem) {
            // 每次处理一条记录，不会一次性加载所有数据到内存
            if(0 == $lyItem->contents->count()){
                $alias = $lyItem->alias;
                $url = "https://r1.zyqstx.net/devotionals/devotionals-mw/devotionals-mw-{$alias}";
                $this->contentSyncService->save($lyItem, $url, $alias, $lyMeta);
                Log::debug(__CLASS__,[$lyItem->id, $alias]);
            }
        }
    }
}
