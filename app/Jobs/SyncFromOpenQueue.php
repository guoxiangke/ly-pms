<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Open\Item;
use App\Models\Open\Program;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\LtsMeta;
use App\Models\LtsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncFromOpenQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Item::chunk(200, function (Collection $items) {
            foreach ($items as $item) {
                if(Str::startsWith($item->alias, 'vsp'))  $item->alias = 'ma' . $item->alias;
                if(Str::startsWith($item->alias, 'ma')){
                    $code = substr($item->alias, 0, strlen($item->alias)-2); //vhx1 vpd0
                    $ltsMeta = LtsMeta::firstOrCreate(['code'=>$code], ['name'=>'created by import ' . $code ,'count'=>0]);
                    $update = [
                        'lts_meta_id'=>$ltsMeta->id,
                        'play_at'=>$item->play_at,
                        'description'=>$item->description
                    ];
                    LtsItem::updateOrCreate(['alias' => $item->alias], $update);
                    Log::debug(__CLASS__, [$item->id, $item->alias, $code]);
                }else{
                    // cc230925
                    $code = 'ma' . preg_replace('/\d+/','',$item->alias);
                    $lyMeta = LyMeta::firstOrCreate(['code'=> $code], ['name'=>'created by import','unpublished_at'=>now()]);
                    $update = [
                        'ly_meta_id'=>$lyMeta->id,
                        // 'play_at'=>$item->play_at,
                        'description'=>$item->description
                    ];
                    $alias = 'ma' . $item->alias;
                    LyItem::updateOrCreate(['alias'=>$alias], $update);
                    Log::debug(__CLASS__, [$item->id, $alias, $code]);
                }
            }
        });


        Program::chunk(200, function (Collection $programs) {
            foreach ($programs as $program) {
                if($program->end_at){
                    $lyMeta = LyMeta::updateOrCreate(['code'=> 'ma'.$program->alias], [
                        'unpublished_at' => now(), //'下架日期，强制不显示'
                        'end_at' => $program->end_at, //'停播日期'
                        'description' => $program->brief,
                    ]);

                    $lyMeta->setMeta('program_phone_time', $program->phone_open);
                    $lyMeta->setMeta('program_sms_keyword', $program->sms_keyword);
                    $lyMeta->setMeta('program_email', $program->email);
                    $lyMeta->setMeta('description_detail', $program->description);
                }
                
            }
        });

    }
}
