<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncItemQueue;
use App\Jobs\SyncProgramQueue;

class SyncOpen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-open';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from ly-open api system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncProgramQueue::dispatch();
        SyncItemQueue::dispatch();
    }
}
