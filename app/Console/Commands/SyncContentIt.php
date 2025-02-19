<?php

namespace App\Console\Commands;

use App\Services\ContentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use voku\helper\HtmlDomParser;
use Illuminate\Support\Facades\Http;
use App\Models\LyItem;
use App\Models\LyMeta;
use Illuminate\Support\Facades\Log;


class SyncContentIt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // 《与神同行》讲章 https://r1.zyqstx.net/sermon/sermon-it
    // https://r1.zyqstx.net/sermon/sermon-it?start=0&limit=20000
    protected $signature = 'app:sync-content-it';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从资源库同步it旷野吗哪的内容（从index获取链接）';

    private $contentSyncService;
    public function __construct(ContentSyncService $contentSyncService)
    {
        parent::__construct();
        $this->contentSyncService = $contentSyncService;
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(App::environment('local')) {
            $this->sync(20000);
        }else{
            $this->sync();
        }
    }

    private function sync($limit=5)
    {
        $code = 'it';
        $lyMeta = LyMeta::where('code', $code)->first();
        $url = "https://r1.zyqstx.net/sermon/sermon-it?start=0&limit={$limit}";
        $response = Http::get($url);
        $dom = HtmlDomParser::str_get_html($response->body());

        $items = [];
        foreach ($dom->find('td.list-title') as $moduleDiv) {
            $a = $moduleDiv->findOne('a');
            $link = $a->getAttribute('href');
            // $text = $a->text(); //Category Title
            $basename = basename($link);
            $alias = last(explode('-', $basename));
            $lyItem = LyItem::where('alias', $alias)->first();
            // 如果已经存在，则跳过
            if($lyItem && $lyItem->contents()->count()) continue;
            $url = 'https://r1.zyqstx.net' . $link;
            $items = array_unshift($items, compact('lyItem', 'url', 'alias'));
        }
        // joomla ?start=0&limit={$limit} 在url添加一个什么query，可以反向排序，
        foreach ($items as $item) {
            $this->contentSyncService->save($item['lyItem'], $item['url'], $item['alias'], $lyMeta);
        }

    }
}
