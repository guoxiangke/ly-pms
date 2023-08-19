<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DownloadTingDaoItemQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $item;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $item = $this->item;
        $id = $item['zjid'];
        // $storage = Storage::disk('local');
        // $directory = "/public/$id/";
        $storage = Storage::disk('r2-tingdao');
        $directory = "/$id/";
        $url = $item['video_url'];
        $name = basename($url);//uRgxyOV3XzoA.mp3
        $filename = "$directory/".basename($url);
        if(Storage::exists($filename)){
            return Log::error('Exists',[$id, $item['key'],$url]);
        }

        // 放到下载队列里
        Log::info("Dowloading start",[$id, $item['key'],$url]);
        $done = $storage->put($filename, file_get_contents($url));
        if($done){
            Log::info("Dowloading done!",[$id, $item['key'],$url]);
        }else{
            Log::error("Dowloading failed!",[$id, $item['key'],$url]);
        }
    }
}
