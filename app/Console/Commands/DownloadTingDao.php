<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\DownloadTingDaoItemQueue;


class DownloadTingDao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tingdao:download {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        // $storage = Storage::disk('local');
        // $directory = "/public/$id/";
        $storage = Storage::disk('r2-tingdao');
        $directory = "/$id/";
        $storage->makeDirectory($directory);
        
        $response = Http::asForm()->post('https://www.tingdao.org/index/Sermon/details',[
            'id'=>$id,
            'order'=>'倒序',
        ]);
        //id.json
        $json = $response->json();
        $storage->put("$directory/$id.json", json_encode($json));
        //id.jpg
        $img = $json['details'][0]['img_url'];
        $storage->put("$directory/$id.jpg", file_get_contents($img));

        foreach ($json['list'] as $key => $item) {
            $item['zjid'] = $id;
            $item['key'] = $key;
            DownloadTingDaoItemQueue::dispatch($item);
        }
        return Command::SUCCESS;
    }
}
