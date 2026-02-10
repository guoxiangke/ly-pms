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

class RlyExportBe extends Command
{
    /**
     * The name and signature of the console command.
     * php -d max_execution_time=600 artisan app:export-rly-be
     *
     * @var string
     */
    protected $signature = 'app:export-rly-be';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'export be content from r.ly729.net';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $beIndexLinks = $this->getBeIndexLinks();
        $moreSeries = [];
        $indexUrls = [];
        foreach ($beIndexLinks as $text0 => $beIndexLink) {
            if(str_ends_with($beIndexLink,'home')){
                // $url = "https://r.729ly.net/exposition/exposition-be/exposition-be-$beIndexLink";
                // // 发送HTTP请求获取页面内容
                // $response = Http::retry(3, 200)       // 重试 3 次，每次间隔 200ms
                //     ->timeout(10)
                //     ->connectTimeout(5)
                //     ->get($url);
                // $html = $response->body();
                // // 使用HtmlDomParser解析HTML
                // $dom = HtmlDomParser::str_get_html($html);
                // //".cat-children"
                // foreach ($dom->find('.cat-children h3 a') as $a) {
                //     $link = $a->getAttribute('href');
                //     $text = $a->text();//seriesText
                //     $moreSeries[$beIndexLink][] = compact('link','text');
                //     $indexUrls[] = [
                //         'link'=>"https://r.729ly.net{$link}?start=0&limit=500",
                //         'text1' => $text,
                //         'text0' => $text0,
                //     ];
                // }
            }else{
                $indexUrls[] = [
                    'link'=>"https://r.729ly.net/exposition/exposition-be/exposition-be-{$beIndexLink}?start=0&limit=500",
                    'text1' => '',
                    'text0' => $text0,
                ];

                // $indexUrls[] = "https://r.729ly.net/exposition/exposition-be/exposition-be-ot-pentateuch-leviticus-home/exposition-be-ot-pentateuch-leviticus?start=0&limit=500";
            }

        }
        // 
        foreach ($indexUrls as $key => $indexUrl) {
            $text0 = $indexUrl['text0'];
            $text1 = $indexUrl['text1'];
            $response = Http::retry(3, 200)       // 重试 3 次，每次间隔 200ms
    ->timeout(10)
    ->connectTimeout(5)
    ->get($indexUrl['link']);
            $html = $response->body();
            // 使用HtmlDomParser解析HTML
            $dom = HtmlDomParser::str_get_html($html);
            $detailLinks = [];
            foreach ($dom->find('.list-title a') as $a) {
                $link = $a->getAttribute('href');
                $text2 = $a->text();//ArticleTitle
                $detailLinks[] = compact('link','text0','text1','text2');
            }

            // 抓去页面并把数据保存到excle表中。
            foreach ($detailLinks as $key => $detailLink) {
                try {
                    $text0 = $detailLink['text0'];
                    $text1 = $detailLink['text1'];
                    $text2 = $detailLink['text2'];
                    $url = 'https://r.729ly.net' . $detailLink['link'];
                    $response = Http::retry(3, 200)
                        ->timeout(10)
                        ->connectTimeout(5)
                        ->get($url);
                    $html = $response->body();
                    
                    // 使用HtmlDomParser解析HTML
                    $dom = HtmlDomParser::str_get_html($html);

                    // TODO 提取mp3的url链接
                    $pattern = "/file:\s*'(https?:\/\/[^']+\.mp3)'/";
                    preg_match($pattern, $html, $match);
                    $mp3_url = $match[1]??'error';

                    // 移除其中播放器 class="module"的<div>
                    foreach ($dom->find('div.module') as $moduleDiv) {
                        $moduleDiv->outertext = '';
                    }
                    // 移除其中class="attachmentsContainer"的<div>
                    foreach ($dom->find('div.attachmentsContainer') as $moduleDiv) {
                        $moduleDiv->outertext = '';
                    }

                    // 提取具有itemprop="articleBody"的<div>
                    $articleBody = $dom->findOne('div[itemprop="articleBody"]')->innerHtml();

                    $articleTitle = $dom->findOne('h2[itemprop="headline"]')->text();

                    $series = $dom->findOne('.page-header a')->text();

                    // 转换HTML为Markdown
                    $converter = new HtmlConverter();
                    $markdown = $converter->convert($articleBody);
                    
                    // 保存Markdown文件
                    $mdDir = "markdowns/{$series}";
                    Storage::makeDirectory($mdDir);
                    $mdFileName = "{$mdDir}/{$articleTitle}.md";
                    Storage::put($mdFileName, $markdown);
                    
                    // 准备CSV数据（不带正文）
                    $csvDataSimple = [
                        '文章标题' => $articleTitle,
                        '系列标题' => $series,
                        'Index页标题' => $text1,
                        '列表标题' => $text2,
                        'Index简写' => $text0,
                        '页面URL' => $url,
                        'mp3地址' => $mp3_url,
                    ];
                    
                    // 准备CSV数据（带正文）
                    $csvDataFull = array_merge($csvDataSimple, [
                        '文章正文' => $articleBody,
                    ]);
                    
                    // 保存简化版CSV
                    $simpleDir = 'csv_simple';
                    Storage::makeDirectory($simpleDir);
                    $fileNameSimple = "{$simpleDir}/{$series}.csv";
                    
                    if (!Storage::exists($fileNameSimple)) {
                        $file = fopen(storage_path('app/' . $fileNameSimple), 'w');
                        fwrite($file, "\xEF\xBB\xBF");
                        fputcsv($file, array_keys($csvDataSimple));
                    } else {
                        $file = fopen(storage_path('app/' . $fileNameSimple), 'a');
                    }
                    fputcsv($file, array_values($csvDataSimple));
                    fclose($file);
                    
                    // 保存完整版CSV
                    $fullDir = 'csv_full';
                    Storage::makeDirectory($fullDir);
                    $fileNameFull = "{$fullDir}/{$series}.csv";
                    
                    if (!Storage::exists($fileNameFull)) {
                        $file = fopen(storage_path('app/' . $fileNameFull), 'w');
                        fwrite($file, "\xEF\xBB\xBF");
                        fputcsv($file, array_keys($csvDataFull));
                    } else {
                        $file = fopen(storage_path('app/' . $fileNameFull), 'a');
                    }
                    fputcsv($file, array_values($csvDataFull));
                    fclose($file);
                    
                    $this->info("已写入: {$articleTitle}");
                    
                    sleep(1);
                    
                } catch (\Exception $e) {
                    // 记录错误到日志文件
                    $errorLog = [
                        '系列' => $text1 ?? 'unknown',
                        '标题' => $text2 ?? 'unknown',
                        'URL' => $url ?? 'unknown',
                        '错误' => $e->getMessage(),
                        '时间' => date('Y-m-d H:i:s'),
                    ];
                    
                    $errorFile = 'error_log.csv';
                    if (!Storage::exists($errorFile)) {
                        $file = fopen(storage_path('app/' . $errorFile), 'w');
                        fwrite($file, "\xEF\xBB\xBF");
                        fputcsv($file, array_keys($errorLog));
                    } else {
                        $file = fopen(storage_path('app/' . $errorFile), 'a');
                    }
                    fputcsv($file, array_values($errorLog));
                    fclose($file);
                    
                    $this->error("错误: {$text2} - {$e->getMessage()}");
                    continue;
                }

            }

        }


        return $moreSeries;
    }


    function getBeIndexLinks(){
        return [
            "创"     => "ot-pentateuch-genesis",
            "出"     => "ot-pentateuch-exodus",
            "利"     => "ot-pentateuch-leviticus-home",
            "民"     => "ot-pentateuch-numbers-home",
            "申"     => "ot-pentateuch-deuteronomy",
            "书"     => "ot-history-joshua-home",
            "士"     => "ot-history-judges-home",
            "得"     => "ot-history-ruth",
            "撒上"   => "ot-history-samuel-one",
            "撒下"   => "ot-history-samuel-two",
            "撒上下" => "ot-history-samuel-one-two",
            "王上下" => "ot-history-kings-one-two",
            "代上"   => "ot-history-chronicles-one",
            "代下"   => "ot-history-chronicles-two",
            "拉"     => "ot-history-ezra-home",
            "尼"     => "ot-history-nehemiah-home",
            "斯"     => "ot-history-esther",
            "伯"     => "ot-poetic-books-job",
            "诗"     => "ot-poetic-books-psalms-home",
            "箴"     => "ot-poetic-books-proverbs",
            "传"     => "ot-poetic-books-ecclesiastes-home",
            "歌"     => "ot-poetic-books-songofsolomon",
            "赛"     => "ot-major-prophets-isaiah",
            "耶"     => "ot-major-prophets-jeremiah",
            "哀"     => "ot-major-prophets-lamentations",
            "结"     => "ot-major-prophets-ezekiel",
            "但"     => "ot-major-prophets-daniel",
            "何"     => "ot-minor-prophets-hosea",
            "珥"     => "ot-minor-prophets-joel",
            "摩"     => "ot-minor-prophets-amos-home",
            "俄"     => "ot-minor-prophets-obadiah",
            "拿"     => "ot-minor-prophets-jonah",
            "弥"     => "ot-minor-prophets-micah",
            "鸿"     => "ot-minor-prophets-nahum",
            "哈"     => "ot-minor-prophets-habakkuk",
            "番"     => "ot-minor-prophets-zephaniah",
            "该"     => "ot-minor-prophets-haggai",
            "亚"     => "ot-minor-prophets-zechariah",
            "玛"     => "ot-minor-prophets-malachi",
            "太"     => "nt-gospels-matthew-home",
            "可"     => "nt-gospels-mark",
            "路"     => "nt-gospels-luke",
            "约"     => "nt-gospels-john-home",
            "徒"     => "nt-history-acts",
            "罗"     => "nt-epistles-of-paul-romans-home",
            "林前"   => "nt-epistles-of-paul-corinthians-one",
            "林后"   => "nt-epistles-of-paul-corinthians-two-home",
            "加"     => "nt-epistles-of-paul-galatians",
            "弗"     => "nt-epistles-of-paul-ephesians-home",
            "腓"     => "nt-epistles-of-paul-philippians-home",
            "西"     => "nt-epistles-of-paul-colossians",
            "帖前"   => "nt-epistles-of-paul-thessalonians-one",
            "帖后"   => "nt-epistles-of-paul-thessalonians-two",
            "提前"   => "nt-epistles-of-paul-timothy-one-home",
            "提后"   => "nt-epistles-of-paul-timothy-two-home",
            "多"     => "nt-epistles-of-paul-titus-home",
            "门"     => "nt-epistles-of-paul-philemon-home",
            "来"     => "nt-general-epistles-hebrews-home",
            "雅"     => "nt-general-epistles-james",
            "彼前"   => "nt-general-epistles-peter-one",
            "彼前后" => "nt-general-epistles-peter-one-two",
            "约壹贰叁"      => "nt-general-epistles-john-one-two-three-home",
            "犹"     => "nt-general-epistles-jude-home",
            "启"     => "nt-prophecy-revelation"
        ];
    }
}