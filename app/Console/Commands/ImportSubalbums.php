<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Clip;
use App\Models\LyItem;
use App\Models\LyMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 导入由转录+校对流水线整理出的「系列子专辑」。
 *
 * 数据来自 feishu_minutes/proofread/ref/：
 *   dy_subalbum_ready_summary.csv  专辑级（系列名/集数/首末播）
 *   dy_subalbum_ready.csv          每集 clip（base=LyItem.alias / 起 / 时长秒）
 *
 * 期次序号取自 PMS 简介里主持人手写的权威清单，起止时间由 ASR 字幕定位。
 * 重跑幂等：同名 album 会先清掉旧 clip 再重建。
 */
class ImportSubalbums extends Command
{
    protected $signature = 'subalbum:import
        {--summary=/tmp/ready_sum.csv : 专辑级 CSV}
        {--clips=/tmp/ready.csv : 每集 clip CSV}
        {--meta-code=dy : 归属节目的 LyMeta.code}
        {--limit=0 : 只导入前 N 个专辑，0=全部}
        {--prune : 删除本节目下已不在 CSV 里的子专辑（系列改名/合并后清理残留）}
        {--dry-run : 只预演不写库}';

    protected $description = '导入系列子专辑（album + clips + clipables）';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $meta = LyMeta::where('code', $this->option('meta-code'))->first();
        if (! $meta) {
            $this->error('找不到 LyMeta code=' . $this->option('meta-code'));
            return self::FAILURE;
        }
        $this->info("归属节目：LyMeta#{$meta->id} {$meta->name}" . ($dry ? '（DRY-RUN）' : ''));

        $albums = $this->readCsv($this->option('summary'));
        $clips = $this->readCsv($this->option('clips'));
        if (($n = (int) $this->option('limit')) > 0) {
            $albums = array_slice($albums, 0, $n);
        }

        $bySeries = [];
        foreach ($clips as $c) {
            $bySeries[$c[0]][] = $c;
        }

        $userId = DB::table('users')->min('id') ?: 1;
        $okAlbum = $okClip = $skip = 0;
        $nManual = 0;

        foreach ($albums as [$name, $cnt, $totSec, $totHour, $first, $last]) {
            $rows = $bySeries[$name] ?? [];
            if (! $rows) {
                $this->warn("  跳过「{$name}」：无 clip 数据");
                continue;
            }
            usort($rows, fn ($a, $b) => (int) $a[1] <=> (int) $b[1]);

            $pattern = $this->describePattern($rows);
            $desc = sprintf(
                "《%s》（%s）的系列子专辑，共 %d 集，总时长 %s 小时。\n首播 %s，末播 %s。%s\n每集为节目中的片段（见 clips 的起止时间），期次依据 PMS 节目简介，时间由 ASR 字幕定位。",
                $meta->name, $meta->code, count($rows), $totHour, $first, $last,
                $pattern ? "\n播出规律：{$pattern}。" : ''
            );

            if ($dry) {
                $pat = $this->describePattern($rows);
                $this->line(sprintf('  [dry] %-26s %3d 集  Manual  %s', mb_substr($name, 0, 26),
                    count($rows), $pat ?: '（播出无固定规律）'));
                $okAlbum++;
                $okClip += count($rows);
                $nManual++;
                continue;
            }

            DB::transaction(function () use ($name, $desc, $meta, $rows, $userId, &$okAlbum, &$okClip, &$skip, &$nManual) {
                // 一律建成 Manual 专辑。
                //
                // 两种专辑的语义区别（这是领域概念，不是技术选型）：
                //   RRule 专辑 —— 按播出规律收录「整期节目」，每一集就是完整的一期；
                //   Manual 专辑 —— 收录从节目里「截取的片段」，靠 clip 的 begin_at/length 定位。
                //
                // 系列子专辑（如「天路历程」第 N 集）是从某期《献上今天》里截出的一段，
                // 不是整期，所以无论播出有没有规律，都只能是 Manual。
                // 播出规律信息写进 description 供人参考，不写 rrule 字段。
                $album = Album::firstOrNew(['name' => $name]);
                $album->description = $desc;
                $album->target_id = null;
                $album->target_type = null;
                $album->rrule = null;
                $album->save();
                $nManual++;

                // 幂等：重跑先清旧 clip 及其关联
                $oldIds = Clip::where('album_id', $album->id)->pluck('id');
                if ($oldIds->isNotEmpty()) {
                    DB::table('clipables')->whereIn('clip_id', $oldIds)->delete();
                    Clip::whereIn('id', $oldIds)->forceDelete();
                }

                foreach ($rows as [$series, $seq, $playDate, $base, $begin, $end, $lenSec]) {
                    $item = LyItem::where('alias', $base)->first();
                    if (! $item) {
                        $skip++;
                        continue;
                    }
                    $playAt = $item->play_at ? $item->play_at->format('Y-m-d') : trim($playDate);
                    $clip = Clip::create([
                        'begin_at'   => $this->toSeconds($begin),
                        'length'     => (int) $lenSec,
                        'title'      => sprintf('%s（%d）%s', $series, (int) $seq, $playAt),
                        'user_id'    => $userId,
                        'album_id'   => $album->id,
                        'sort_order' => (int) $seq,
                    ]);
                    DB::table('clipables')->insert([
                        'clipable_type' => LyItem::class,
                        'clipable_id'   => $item->id,
                        'clip_id'       => $clip->id,
                    ]);
                    $okClip++;
                }
                $okAlbum++;
            });
            $this->line(sprintf('  ✓ %-28s %3d 集', mb_substr($name, 0, 28), count($rows)));
        }

        // 系列改名/合并后，旧名字的专辑会留在库里 —— 导入只管新增更新，不会自动删。
        // --prune 清掉「本节目下、由本命令建的、但已不在 CSV 里」的专辑。
        if ($this->option('prune') && (int) $this->option('limit') === 0) {
            $wanted = array_column($albums, 0);
            $stale = Album::whereNull('target_id')->whereHas('clips')
                ->with('clips.lyItems.ly_meta')->get()
                ->filter(fn ($a) => $a->resolvedTarget()?->id === $meta->id)
                ->reject(fn ($a) => in_array($a->name, $wanted, true));
            foreach ($stale as $a) {
                $this->warn(sprintf('  - 清理孤立专辑「%s」（%d 集）', $a->name, $a->clips->count()));
                if (! $dry) {
                    $ids = $a->clips->pluck('id');
                    DB::table('clipables')->whereIn('clip_id', $ids)->delete();
                    Clip::whereIn('id', $ids)->forceDelete();
                    $a->forceDelete();
                }
            }
            if ($stale->isEmpty()) {
                $this->line('  （无孤立专辑）');
            }
        }

        $this->newLine();
        $this->info("专辑 {$okAlbum} 个（均为 Manual），clip {$okClip} 条" . ($skip ? "，跳过 {$skip} 条（LyItem 缺失）" : ''));
        if (! $dry) {
            $this->info('当前 albums=' . Album::count() . ' clips=' . Clip::count());
        }

        return self::SUCCESS;
    }


    /**
     * 描述播出规律（仅写进 description 供人参考，不生成 rrule —— 子专辑一律 Manual）。
     *
     * dy 的系列子专辑实测有两种形态：
     *   连播   —— 周一~周五（或周一~周四）每天一集，跨周末间隔 3~4 天
     *   每周一次 —— 固定某个星期几，间隔 7 天
     * 判据：落在主星期几集合内的集数占比 >= 90%，且日期跨度与集数相符。
     */
    private function describePattern(array $rows): ?string
    {
        $dates = [];
        foreach ($rows as $r) {
            $b = $r[3];                       // dy200722
            $dates[] = mktime(0, 0, 0, (int) substr($b, 4, 2), (int) substr($b, 6, 2), 2000 + (int) substr($b, 2, 2));
        }
        sort($dates);
        if (count($dates) < 3) {
            return null;
        }

        $map = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
        $cnt = [];
        foreach ($dates as $t) {
            $w = (int) date('N', $t) - 1;     // 0=Mon
            $cnt[$w] = ($cnt[$w] ?? 0) + 1;
        }
        arsort($cnt);

        // 取覆盖 >=90% 的最小星期几集合
        $total = count($dates);
        $days = [];
        $acc = 0;
        foreach ($cnt as $w => $n) {
            $days[] = $w;
            $acc += $n;
            if ($acc / $total >= 0.9) {
                break;
            }
        }
        if ($acc / $total < 0.9) {
            return null;                      // 星期分布太散，没有规律
        }
        sort($days);

        // 吻合度校验：把 rrule 展开，看生成的日期集合与实际播出差多少。
        // 中间有长停播的系列（跨春节、跨半年）会让 rrule 多算出一堆没播的日子，
        // 这种 rrule 是假语义 —— 宁可建成 Manual，靠 clips 显式列举。
        $gen = 0;
        for ($t = $dates[0]; $t <= end($dates); $t = strtotime('+1 day', $t)) {
            if (in_array((int) date('N', $t) - 1, $days, true)) {
                $gen++;
            }
        }
        if ($gen > $total * 1.1) {
            return null;                      // 中间有长停播，说不上"规律"
        }

        $cn = ['一', '二', '三', '四', '五', '六', '日'];
        $names = array_map(fn ($w) => '周' . $cn[$w], $days);

        return count($days) === 1 ? "每{$names[0]}" : ('每周 ' . implode('、', $names));
    }

    private function readCsv(string $path): array
    {
        $rows = array_map('str_getcsv', file($path));
        array_shift($rows);
        return array_values(array_filter($rows, fn ($r) => count($r) > 1));
    }

    /** "22:58" / "1:22:58" -> 秒 */
    private function toSeconds(string $t): int
    {
        $p = array_map('intval', explode(':', trim($t)));
        return count($p) === 3 ? $p[0] * 3600 + $p[1] * 60 + $p[2] : $p[0] * 60 + $p[1];
    }
}
