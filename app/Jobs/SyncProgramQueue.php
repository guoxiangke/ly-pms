<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Open\Program;
use App\Models\LyMeta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncProgramQueue implements ShouldQueue
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
