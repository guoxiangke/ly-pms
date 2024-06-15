<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LyMeta;

class WriteID3TagAndSync2S3Queue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tempFilePath = '';
    public $description = null;

    /**
     * Create a new job instance.
     */
    public function __construct($tempFilePath, $description)
    {
        $this->tempFilePath =  $tempFilePath;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LyMeta::writeID3TagAndSync2S3($this->tempFilePath, $this->description);
    }
}
