<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class ImportBibleTags extends Command
{
    protected $signature = 'import-bible-tags';

    protected $description = '从 bible.json 导入圣经66卷书为 type=bible 的 Tag（幂等，可重复执行）';

    public function handle(): int
    {
        $path = storage_path('app/bible.json');

        if (! file_exists($path)) {
            $this->error("文件不存在: {$path}");
            return 1;
        }

        $books = json_decode(file_get_contents($path), true);
        $created = 0;
        $updated = 0;

        foreach ($books as $book) {
            $osis = $book['osis'];
            $abbrCn = $book['abbr_cn'];
            $bookNameEn = $book['book_name_en'];
            $numBook = $book['num_book'];

            $nameJson = json_encode(['en' => $osis, 'zh' => $abbrCn], JSON_UNESCAPED_UNICODE);
            $slugJson = json_encode(['en' => 'bible/' . $osis, 'zh' => 'bible/' . $osis], JSON_UNESCAPED_UNICODE);

            // 按 osis(en) + type 检查是否已存在
            $existing = Tag::query()
                ->where('type', 'bible')
                ->whereJsonContains('name->en', $osis)
                ->first();

            if ($existing) {
                // 已存在则更新确保数据正确
                DB::table('tags')
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $nameJson,
                        'slug' => $slugJson,
                        'order_column' => $numBook,
                    ]);
                $updated++;
                $this->line("  更新: {$abbrCn} ({$osis}) — id={$existing->id}");
                continue;
            }

            $tag = Tag::findOrCreateFromString($abbrCn, 'bible');

            DB::table('tags')
                ->where('id', $tag->id)
                ->update([
                    'name' => $nameJson,
                    'slug' => $slugJson,
                    'order_column' => $numBook,
                ]);

            $created++;
            $this->info("  创建: {$abbrCn} ({$osis}) — bible/{$bookNameEn} [#{$numBook}]");
        }

        $this->newLine();
        $this->info("=== 完成: 创建 {$created} 个，更新 {$updated} 个 ===");

        return 0;
    }
}
