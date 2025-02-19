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


class SyncContentMw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-content-mw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从资源库同步mw旷野吗哪的内容（日期连续）';

    private $contentSyncService;
    public function __construct(ContentSyncService $contentSyncService)
    {
        parent::__construct();
        $this->contentSyncService = $contentSyncService;
    }
    private function sync($startDate)
    {
        // From 2018年11月1日 开始 到 今天 从 https://r1.zyqstx.net/devotionals/devotionals-mw/devotionals-mw-mw250214 获取html（使用Http::get），并转换成markdown，注意地址中的日期是个变量
        // 调用 从html中找到这个div itemprop="articleBody"， 然后 remove其中的 class="module" 的div，然后将剩下的html转成
        $endDate = Carbon::now();

        // 创建HTML转换器实例
        $converter = new HtmlConverter();
        $code = 'mw';

        // 循环遍历日期范围
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            // 格式化日期为所需的字符串格式
            $dateStr = $date->format('ymd');
            $alias = $code . $dateStr;
            $lyMeta = LyMeta::where('code', $code)->first();
            $lyItem = LyItem::where('alias', $alias)->first();
            // 如果已经存在，则跳过
            if($lyItem && $lyItem->contents()->count()) continue;
            $url = "https://r1.zyqstx.net/devotionals/devotionals-mw/devotionals-mw-mw{$dateStr}";
            $this->contentSyncService->save($lyItem, $url, $alias, $lyMeta);
        }
    }

    /**
     * Execute the console command.
     */

    public function handle()
    {
        if(App::environment('local')) {
            $startDate = Carbon::create(2018, 11, 1);
            $this->sync($startDate);
        }else{
            $startDate = Carbon::now()->subDay();
            $this->sync($startDate);
        }
    }
}
