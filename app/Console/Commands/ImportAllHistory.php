<?php

namespace App\Console\Commands;

use App\Models\LyMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportAllHistory extends Command
{
    protected $signature = 'import-all-history
                            {--stopped : 仅已停播节目}
                            {--all : 所有节目（含在播）}';

    protected $description = '批量从 CDN 导入历史 LyItem + 创建年度专辑（幂等，可重复执行）';

    public function handle(): int
    {
        if (!$this->option('stopped') && !$this->option('all')) {
            $this->error('请指定 --stopped（仅停播）或 --all（所有节目）');
            return 1;
        }

        $query = LyMeta::query();
        if ($this->option('stopped')) {
            $query->whereNotNull('end_at')->where('end_at', '<=', now());
        }

        $metas = $query->orderBy('id')->get();
        $this->info("共 {$metas->count()} 个节目待处理");
        $this->newLine();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($metas as $meta) {
            $this->info("=== [{$meta->id}] {$meta->name} (code={$meta->code}) ===");

            // 1. 导入历史 LyItem
            $this->line("  > 导入历史数据...");
            try {
                $exitCode = Artisan::call('create-lyitems-history', ['code' => $meta->code]);
                $output = trim(Artisan::output());
                // 输出每行缩进
                collect(explode("\n", $output))->each(fn ($line) => $this->line("    {$line}"));

                if ($exitCode !== 0) {
                    $this->warn("    导入失败（可能无 CDN 数据），继续...");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->warn("    导入异常: {$e->getMessage()}，继续...");
                $skipped++;
            }

            // 2. 创建年度专辑
            $this->line("  > 创建年度专辑...");
            try {
                Artisan::call('create-lymeta-year-album', ['code' => $meta->code]);
                $output = trim(Artisan::output());
                collect(explode("\n", $output))->each(fn ($line) => $this->line("    {$line}"));
                $success++;
            } catch (\Exception $e) {
                $this->warn("    年度专辑异常: {$e->getMessage()}");
                $failed++;
            }

            $this->newLine();
        }

        $this->info("全部完成！成功 {$success} 个，跳过 {$skipped} 个，失败 {$failed} 个");

        return 0;
    }
}
