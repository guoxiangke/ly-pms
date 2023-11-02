<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncItemsFromOpenQueue;

class SyncOpenItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-open-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncItemsFromOpenQueue::dispatch();
    }
}
