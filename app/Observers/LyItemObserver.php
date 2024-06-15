<?php

namespace App\Observers;

use App\Models\LyItem;
use App\Models\LyMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LyItemObserver
{
    /**
     * Handle the LyItem "created" event.
     */
    public function created(LyItem $lyItem): void
    {
        if($lyItem->play_at) return ;// 514
        $ymd = preg_replace('/\D+/', '', $lyItem->alias) . " 00:00:00";
        $play_at = Carbon::createFromFormat('ymd H:i:s', $ymd);
        $lyItem->update([
            'play_at' => $play_at
        ]);
        Log::debug(__CLASS__,[$lyItem->alias]);
    }

    /**
     * Handle the LyItem "updated" event.
     */
    public function updated(LyItem $lyItem): void
    {
        if($lyItem->isDirty('mp3') && $lyItem->mp3){
            LyMeta::writeID3TagAndSync2S3(storage_path('/app/public/'.$lyItem->mp3), $lyItem->description);
        }
    }

    /**
     * Handle the LyItem "deleted" event.
     */
    public function deleted(LyItem $lyItem): void
    {
        //
    }

    /**
     * Handle the LyItem "restored" event.
     */
    public function restored(LyItem $lyItem): void
    {
        //
    }

    /**
     * Handle the LyItem "force deleted" event.
     */
    public function forceDeleted(LyItem $lyItem): void
    {
        //
    }
}
