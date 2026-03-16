<?php

namespace App\Console\Commands;

use App\Models\LyItem;
use App\Models\LyMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CreateLyitemsHistory extends Command
{
    protected $signature = 'create-lyitems-history {code : 节目代码，如 bc}';

    protected $description = '从 CDN 数据源导入历史 LyItem 记录（早于数据库中最早记录的部分）';

    private const CDN_BASE = 'https://pywbjs.azureedge.net/ajso';

    public function handle(): int
    {
        $code = $this->argument('code');

        $lyMeta = LyMeta::where('code', $code)->first();
        if (!$lyMeta) {
            $this->error("未找到节目代码: {$code}");
            return 1;
        }

        $this->info("节目: {$lyMeta->name} (code={$code}, id={$lyMeta->id})");

        // 找到数据库中最早的 LyItem
        $earliest = LyItem::where('ly_meta_id', $lyMeta->id)
            ->orderBy('play_at')
            ->first();

        $cutoffDate = $earliest?->play_at;
        $this->info("数据库最早记录: " . ($cutoffDate ? "{$earliest->alias} ({$cutoffDate->format('Y-m-d')})" : '无'));

        // 获取专辑列表
        $this->info("正在获取专辑列表...");
        $response = Http::timeout(30)->get(self::CDN_BASE . "/{$code}_albums.json");
        if (!$response->ok()) {
            $this->error("获取专辑列表失败: HTTP {$response->status()}");
            return 1;
        }

        $data = $response->json();
        if (empty($data) || empty($data[0]['albums'])) {
            $this->error("专辑列表数据为空");
            return 1;
        }

        $albums = collect($data[0]['albums'])->sortBy('id');
        $this->info("找到 {$albums->count()} 个专辑");

        $created = 0;
        $skipped = 0;
        $paths = [];
        $stopped = false;

        foreach ($albums as $album) {
            if ($stopped) break;

            $albumId = $album['id'];
            $albumName = trim($album['name']);
            $this->line("  处理: {$albumName} (id={$albumId})");

            // 获取专辑详情
            $songResponse = Http::timeout(30)->get(self::CDN_BASE . "/{$albumId}.json");
            if (!$songResponse->ok()) {
                $this->warn("    获取失败: HTTP {$songResponse->status()}，跳过");
                continue;
            }

            $songs = $songResponse->json();
            if (empty($songs)) continue;

            foreach ($songs as $song) {
                $path = $song['path'] ?? '';
                $title = $song['title'] ?? '';

                // 从 path 提取 alias: https://liangyoult.com/bc/bc130101.mp3 → bc130101
                $alias = pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_FILENAME);
                if (empty($alias)) continue;

                // 从 alias 提取日期
                $ymdStr = preg_replace('/\D+/', '', $alias);
                if (strlen($ymdStr) < 6) continue;

                try {
                    $playAt = Carbon::createFromFormat('ymd', $ymdStr)->startOfDay();
                } catch (\Exception $e) {
                    continue;
                }

                // 如果到达截止日期，停止
                if ($cutoffDate && $playAt >= $cutoffDate) {
                    $this->info("    到达截止日期 {$cutoffDate->format('Y-m-d')}，停止");
                    $stopped = true;
                    break;
                }

                // 记录路径（替换失效域名）
                $path = str_replace('https://liangyoult.com/', 'https://aud.pdbo.uk/', $path);
                $paths[] = $path;

                // 提取 description：去掉日期前缀（如 "12年01月01日 "）
                $description = preg_replace('/^\d{2}年\d{2}月\d{2}日\s*/', '', $title);

                // 创建 LyItem（幂等）
                $item = LyItem::firstOrCreate(
                    ['alias' => $alias],
                    [
                        'ly_meta_id' => $lyMeta->id,
                        'description' => $description,
                    ]
                );

                if ($item->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        // 写入路径文件
        $pathFile = storage_path("app/{$code}_history_paths.txt");
        file_put_contents($pathFile, implode("\n", $paths));

        $this->newLine();
        $this->info("完成！创建 {$created} 个，跳过 {$skipped} 个（已存在）");
        $this->info("共 " . count($paths) . " 个路径已写入: {$pathFile}");

        return 0;
    }
}
