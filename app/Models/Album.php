<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Deligoez\LaravelModelHashId\Traits\HasHashId;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Carbon\Carbon;

class Album extends Model
{
    use HasHashId;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Album $album) {
            $hasTarget = !empty($album->target_id) && !empty($album->target_type);
            $hasRrule = !empty($album->rrule);

            // 必须同时存在或同时不存在，视为 RRule / Manual 两种互斥模式
            if ($hasTarget !== $hasRrule) {
                throw ValidationException::withMessages([
                    'rrule' => 'RRule 专辑必须同时填写 Target Program 与 RRule，Manual 专辑必须同时留空。',
                ]);
            }
        });
    }

    /**
     * 已发布的专辑（published_at 非空）
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * 多态关联到目标模型（暂时只有 LyMeta）
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 手动模式下的 Clips
     */
    public function clips(): HasMany
    {
        return $this->hasMany(Clip::class);
    }

    /**
     * 是否为 rrule 模式
     */
    protected function isRruleMode(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->rrule) && !empty($this->target_id) && !empty($this->target_type),
        );
    }

    /**
     * 统一获取播放列表项
     *
     * rrule 模式：从 target (LyMeta) 的 LyItems 中按 rrule 日期筛选
     * 手动模式：从 clips 关联的 LyItems 中获取
     */
    public function getPlaylistItems(): Collection
    {
        if ($this->is_rrule_mode) {
            return $this->getRrulePlaylistItems();
        }

        return $this->getManualPlaylistItems();
    }

    /**
     * rrule 模式：通过 rrule 计算日期，按 alias 匹配 LyItems
     */
    protected function getRrulePlaylistItems(): Collection
    {
        $target = $this->target;
        if (!$target || !($target instanceof LyMeta)) {
            return collect();
        }

        $dates = $this->resolveRruleDates();
        if ($dates->isEmpty()) {
            return collect();
        }

        // 将日期转换为 alias 格式：{code}{YYMMDD}
        $code = $target->code;
        $aliases = $dates->map(fn (Carbon $d) => $code . $d->format('ymd'))->toArray();

        $lyItems = LyItem::where('ly_meta_id', $target->id)
            ->with('ly_meta', 'contents.attachments')
            ->whereIn('alias', $aliases)
            ->orderBy('play_at', 'ASC')
            ->get();

        return $lyItems->map(function (LyItem $item) {
            return new PlaylistItem(
                lyItem: $item,
                beginAt: 0,
                length: 0,
                title: $item->episode_title,
                path: $item->path,
                playAt: $item->play_at,
            );
        });
    }

    /**
     * 手动模式：从 clips 中获取关联的 LyItems
     */
    protected function getManualPlaylistItems(): Collection
    {
        $clips = $this->clips()
            ->with(['lyItems' => function ($query) {
                $query->with('ly_meta', 'contents.attachments');
            }])
            ->ordered()
            ->get();

        return $clips->flatMap(function (Clip $clip) {
            return $clip->lyItems->map(function (LyItem $item) use ($clip) {
                return new PlaylistItem(
                    lyItem: $item,
                    beginAt: $clip->begin_at,
                    length: $clip->length,
                    title: $clip->title ?? $item->episode_title,
                    path: $item->path,
                    playAt: $item->play_at,
                );
            });
        })->values();
    }

    /**
     * 解析 rrule 字符串，返回日期集合
     */
    public function resolveRruleDates(): Collection
    {
        if (empty($this->rrule)) {
            return collect();
        }

        try {
            $rule = new Rule($this->rrule);
            $transformer = new ArrayTransformer();
            $recurrences = $transformer->transform($rule);

            return collect($recurrences)->map(function ($recurrence) {
                return Carbon::instance($recurrence->getStart());
            });
        } catch (\Exception $e) {
            \Log::error('Album rrule parse error', [
                'album_id' => $this->id,
                'rrule' => $this->rrule,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }
}
