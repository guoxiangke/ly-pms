<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Helper;

class GetFwdYoutubeListLastItemForFfmpeg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fwd:get-youtube-list-last-item-for-ffmpeg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo Command On vapor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Sunday-Play-list
        $playListId = 'PLLDxN82mMW3NrAoY-Nm6JYsk6ib5_5AZf';
        $all = Helper::get_all_items_by_youtube_playlist_id($playListId);
        Storage::disk('r2-share')->put("/playlist/fwd-{$playListId}-test.json", json_encode($all->last()->snippet));
    }
}
