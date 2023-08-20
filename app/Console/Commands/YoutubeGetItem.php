<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Madcoda\Youtube\Facades\Youtube;
use App\Helpers\Helper;

class YoutubeGetItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:get-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo Command On vapor for yt-dlp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Sunday-Play-list
        $playListId = 'PLLDxN82mMW3NrAoY-Nm6JYsk6ib5_5AZf';
        $all = Helper::get_all_items_by_youtube_playlist_id($playListId);
        Storage::disk('r2-share')->put("/playlist/fwd-{$playListId}.json", json_encode($all->last()));

        $ChannelId = 'UCNsVuiIBCoKA3MWoEgd-4Qg'; //CCAC Cantonese Worship - August 13, 2023
        $all = Youtube::searchChannelVideos('CCAC', $ChannelId, $limit=1, $order='date');
        Storage::disk('r2-share')->put("/playlist/cacc-{$ChannelId}.json", json_encode(collect($all)->first()));
    }
}
