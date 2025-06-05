<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncItemQueue;

use App\Models\Open\Item;
use App\Models\Open\Program;
use App\Models\LyItem;
use App\Models\Content;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use voku\helper\HtmlDomParser;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncRly729 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-rly729';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync content from r.ly729.net';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 現時在資源庫的文章 (article), 由 ID 5至20449
        // 可以了， 找到了 https://r.729ly.net/?option=com_content&view=article&id=102

        // $init = new(2025,5,20)
        // $end = 20449 + now()->diffDays();
        // 2136
        // 16345
        // 12332 2 pdf

        $disk = 's3';

        for ($articleId=398; $articleId < 25449 ; $articleId++) {
            Log::info("Processing Article Id: $articleId");
            $url = "https://r.729ly.net/?option=com_content&view=article&id={$articleId}";
            // $url = 'https://r1.zyqstx.net/exposition/exposition-ttb-cttb/exposition-ttb-cttb-guide/exposition-ttb-cttb-0001-guide01-20200330';
                // 发送HTTP请求获取页面内容
            $response = Http::get($url);
            $html = $response->body();
            $html = str_replace('<p>普通话</p>','',$html);
            $html = str_replace('<p>粤语</p>','',$html);

            // 使用HtmlDomParser解析HTML
            $dom = HtmlDomParser::str_get_html($html);

            // 移除其中class="module"的<div> //mp3 player
            foreach ($dom->find('div.module') as $moduleDiv) {
                $moduleDiv->outertext = '';
            }

            // 移除其中class="attachmentsContainer"的<div>
            $downloadLinkHtml = '';
            $pdfFiles = [];

            preg_match_all('/<a class="at_url"\s[^>]*href="([^"]+\.pdf)"[^>]*>.*?<\/a>/i', $html, $matches);
            // dd($matches);
            // $matches[0] 是整个 <a> 标签，$matches[1] 是 href 中的 PDF 链接
            // 下载pdf
            foreach ($matches[1] as $key =>$url) {
                $path = str_replace('https://r1.zyqstx.net/attachments/article/', '', $url);
                // /rly/attachments/article/8937/
                $path = '/rly/attachments/'. str_replace('/','_', $path);
                $name = strip_tags($matches[0][$key]);
                $pdfFiles[$name] = $path;
                // /rly/attachments/12332_08material_c13.pdf
                Storage::disk($disk)->makeDirectory(dirname($path));
                // 判断是否存在
                if(!Storage::disk($disk)->exists($path)){
                    Log::info('Downding PDF File: ' . $path . ' - '. $url);
                    Storage::disk($disk)->put($path, file_get_contents($url));
                }
            }
            // 清空下载 html
            foreach ($dom->find('div.attachmentsContainer') as $moduleDiv) {
                $moduleDiv->outertext = '';
            }

            // 提取具有itemprop="articleBody"的<div>
            $articleBody = $dom->findOne('div[itemprop="articleBody"]');

            $articleTitle = $dom->findOne('h2[itemprop="headline"]')->text();

            // 将剩余的HTML转换为Markdown
            $converter = new HtmlConverter();
            $markdown = $converter->convert($articleBody->innerHtml());

            // 找到所有的audios，并提取alias cc250101.mp3
            // $pattern = "/file:\s*'https:\/\/p\.lydt\.work\/ly\/audio(\/[^\/]+\/[^\/]+\/[^\/]+)\.mp3'/";
            // $pattern = "/file:\s*'https:\/\/[^\/]+\/ly\/audio\/[^\/]+\/[^\/]+\/([^\/]+)\.mp3/";

            $pattern = "/file:\s*'(https:\/\/[^\/]+\/ly\/audio\/(?:[^\/]+\/)*([^\/]+)\.mp3)/";

            preg_match_all($pattern, $html, $matches);
            
            $lyItems = [];
            $audioHtml='';
            foreach ($matches[2] as $key => $alias) {
                // ttb250331
                $code = substr($alias, 0, -6);;
                // 如果 PMS 没有这个 LyItem，则跳过(不创建新的lyItem)
                $lyItem = LyItem::where('alias', $alias)->first();
                if(!$lyItem){
                    // 保存mp3
                    $audioUrl = $matches[1][$key];
                    $audioHtml = $audioHtml . "\r\n<audio href='{$audioUrl}' id='{$articleId}' controls />\r\n\r\n";
                    $path = "/rly/mp3/$articleId.mp3";
                    if(!Storage::disk($disk)->exists($path))
                        Storage::disk($disk)->put($path, file_get_contents($audioUrl));
                    continue;
                }else{
                    $lyItems[] = compact('code','alias');
                }
            }
            // 即没有任何对应，近保存markdown
            $markdown = trim(strip_tags($markdown));
            if(empty($lyItems) &&  ($markdown || $audioHtml) ){
                // 不使用 articleTitle ，而page-header
                // $pageTitle = $dom->findOne('.page-header')->text();
                $markdown = "{$articleTitle}\r\n\r\n" .$audioHtml. $markdown;
                Log::info("Saved md: {$articleId}-{$articleTitle}");
                Storage::disk($disk)->put("/rly/contents/$articleId.md", $markdown);
            }
            if(empty($lyItems)){
                continue;
            }

            $content = Content::firstOrcreate([
                'title' => $articleTitle,
                'body' => $markdown,// . $downloadLinkHtml,
            ],[
                'user_id' => 1,
            ]);
            if($content->id == 549) continue; //空的content
            $content->setMeta('joomla_article_id', $articleId);
            if($content->wasRecentlyCreated){
                // 关联pdf
                Log::info('wasRecentlyCreated contentId:' .  $content->id);
            }else{
                Log::info('已存在 contentId:' .  $content->id . " articleId: $articleId");
            }
            // 关联PDF File
            foreach ($pdfFiles as $name => $path) {
                Log::info('关联PDF File: ' . $path . ' - '. $content->id);
                $content
                   ->addMediaFromDisk($path, $disk)
                   ->usingName($name) // 下载讲义
                   ->preservingOriginal()
                   ->toMediaCollection();
            }
            // 判断是否有content，如果已有，不再创建，再看下一个是否有，没有则关联
            foreach ($lyItems as $lyItem) {
                $alias = $lyItem['alias'];
                $lyItem = LyItem::where('alias', $alias)->first();
                dd($lyItem,$alias);
                // 说明已导入
                if($lyItem->contents->count()){
                    Log::info('已关联: ' . $lyItem['alias'] . ' - ' . $content->id. ' : ' . $lyItem->id);
                }else{
                    $lyItem->contents()->attach($content->id);
                    Log::info('新关联: ' . $lyItem['alias'] . ' - ' . $content->id. ' : ' . $lyItem->id);
                }
            }
        }

    }
}