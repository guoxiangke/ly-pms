<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * 播放列表项的统一数据结构
 * 用于 Album::getPlaylistItems() 的统一输出格式
 */
class PlaylistItem
{
    public function __construct(
        public readonly LyItem $lyItem,
        public readonly int $beginAt,
        public readonly int $length,
        public readonly string $title,
        public readonly string $path,
        public readonly ?Carbon $playAt,
    ) {}

    /**
     * 是否播放完整音频
     */
    public function isFullAudio(): bool
    {
        return $this->beginAt === 0 && $this->length === 0;
    }

    /**
     * 转为数组
     */
    public function toArray(): array
    {
        return [
            'ly_item_id' => $this->lyItem->id,
            'begin_at' => $this->beginAt,
            'length' => $this->length,
            'title' => $this->title,
            'path' => $this->path,
            'play_at' => $this->playAt?->format('Y-m-d'),
            'is_full_audio' => $this->isFullAudio(),
        ];
    }
}
