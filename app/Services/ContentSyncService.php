<?php

namespace App\Services;

use voku\helper\HtmlDomParser;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Content;
use App\Models\LyItem;
use App\Models\LyMeta;

class ContentSyncService
{
    // 从资源库同步内容
    // 当lyItem不存在时，传递  $lyMeta 创建时需要，而不能用 $lyItem->ly_meta->id
    public function save($lyItem, $url, $alias, LyMeta $lyMeta)
    {
        // 发送HTTP请求获取页面内容
        $response = Http::get($url);
        // 使用HtmlDomParser解析HTML
        $dom = HtmlDomParser::str_get_html($response->body());

        // 移除其中class="module"的<div>
        foreach ($dom->find('div.module') as $moduleDiv) {
            $moduleDiv->outertext = '';
        }
        // 移除其中class="attachmentsContainer"的<div>
        foreach ($dom->find('div.attachmentsContainer') as $moduleDiv) {
            $moduleDiv->outertext = '';
        }

        // 提取具有itemprop="articleBody"的<div>
        $articleBody = $dom->findOne('div[itemprop="articleBody"]');

        $articleTitle = $dom->findOne('h2[itemprop="headline"]')->text();

        // 将剩余的HTML转换为Markdown
        $converter = new HtmlConverter();
        $markdown = $converter->convert($articleBody->innerHtml());
        $content = Content::firstOrcreate([
            'title' => $articleTitle
        ],[
            'body' => $markdown,
            'user_id' => 1
        ]);
        if($content->id == 549) {
            Log::debug('lyItem : detach 549' . $alias . ' - ' . $content->id);
            return $lyItem->contents()->detach(549);
        }

        Log::info('lyItem: ' . $alias . ' - ' . $content->id);
        // 创建或更新lyItem
        if(!$lyItem) {
            $lyItem = LyItem::create([
                'alias' => $alias,
                'title' => $articleTitle,
                'description' => 'CBT ' . $alias,
                'ly_meta_id' => $lyMeta->id,
            ]);
        }
        $lyItem->contents()->attach($content->id);
    }
}
