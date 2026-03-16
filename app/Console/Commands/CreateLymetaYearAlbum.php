<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\LyMeta;
use App\Models\LyItem;
use Illuminate\Console\Command;

class CreateLymetaYearAlbum extends Command
{
    protected $signature = 'create-lymeta-year-album {code : 节目代码，如 bc} {year? : 指定年份，不填则从最早年份到去前年份}';

    protected $description = '为指定节目按年创建专辑（幂等，已存在则跳过）';

    public function handle(): int
    {
        $code = $this->argument('code');

        $lyMeta = LyMeta::where('code', $code)->first();
        if (!$lyMeta) {
            $this->error("未找到节目代码: {$code}");
            return 1;
        }

        $this->info("节目: {$lyMeta->name} (code={$code}, id={$lyMeta->id})");

        $year = $this->argument('year');

        if ($year) {
            $years = [(int) $year];
        } else {
            $earliest = LyItem::where('ly_meta_id', $lyMeta->id)
                ->orderBy('play_at')
                ->value('play_at');

            if (!$earliest) {
                $this->error("该节目没有任何单集");
                return 1;
            }

            $startYear = (int) date('Y', strtotime($earliest));
            $endYear = $lyMeta->end_at
                ? (int) $lyMeta->end_at->format('Y')
                : (int) now()->year - 1;
            $years = range($startYear, $endYear);
        }

        $created = 0;
        $skipped = 0;

        foreach ($years as $y) {
            $name = "{$lyMeta->name}{$y}年";

            // 检查是否已存在
            $exists = Album::where('name', $name)
                ->where('target_id', $lyMeta->id)
                ->where('target_type', LyMeta::class)
                ->exists();

            if ($exists) {
                $this->line("  跳过: {$name}（已存在）");
                $skipped++;
                continue;
            }

            // 检查该年是否有数据
            $count = LyItem::where('ly_meta_id', $lyMeta->id)
                ->whereYear('play_at', $y)
                ->count();

            if ($count === 0) {
                $this->line("  跳过: {$name}（无单集数据）");
                $skipped++;
                continue;
            }

            $rrule = "DTSTART:{$y}0101T000000Z\nRRULE:FREQ=DAILY;UNTIL={$y}1231T235900Z";

            Album::create([
                'name' => $name,
                'description' => "{$y}年度专辑",
                'target_id' => $lyMeta->id,
                'target_type' => LyMeta::class,
                'rrule' => $rrule,
                'status' => 'published',
            ]);

            $this->info("  创建: {$name}（{$count} 集）");
            $created++;
        }

        $this->newLine();
        $this->info("完成！创建 {$created} 个，跳过 {$skipped} 个");

        return 0;
    }
}
