<?php

namespace App\Observers;

use App\Models\LyItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LyItemObserver
{
    /**
     * Handle the LyItem "created" event.
     */
    public function created(LyItem $lyItem): void
    {
        $ymd = preg_replace('/\D+/', '', $lyItem->alias) . " 00:00:00";
        $dt = Carbon::createFromFormat('ymd H:i:s', $ymd);
        $lyItem->update([
            'play_at' => $dt
        ]);
        Log::debug(__CLASS__,[$lyItem->alias, $dt]);
    }

    /**
     * Handle the LyItem "updated" event.
     */
    public function updated(LyItem $lyItem): void
    {
        if($lyItem->isDirty('mp3') && $lyItem->mp3){
            // proceess mp3 in queue
            Log::error(__CLASS__,[$lyItem->alias, 'UpdateMp3MetaQueue::process']);
            // UpdateMp3MetaQueue::process($lyItem);
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
