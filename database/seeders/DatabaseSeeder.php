<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


use App\Models\Open\Program;
use App\Models\LyMeta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        $email = "admin@admin.com";
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($email),
        ]);

        $this->call([
            TagsSeeder::class,
            LyMetaSeeder::class,
            LtsMetaSeeder::class,
        ]);

        Program::chunk(200, function (Collection $programs) {
            foreach ($programs as $program) {
                if($program->end_at){
                    $lyMeta = LyMeta::updateOrCreate(['code'=> 'ma'.$program->alias], [
                        'unpublished_at' => now(), //'下架日期，强制不显示'
                        'end_at' => $program->end_at, //'停播日期'
                        'description' => $program->brief,
                        'name' =>  $program->name,
                    ]);
                }else{
                    $lyMeta = LyMeta::updateOrCreate(['code'=> 'ma'.$program->alias], [
                        'end_at' => $program->end_at, //'停播日期'
                        'description' => $program->brief,
                        'name' =>  $program->name,
                    ]);
                }
                $lyMeta->setMeta('program_phone_time', $program->phone_open);
                $lyMeta->setMeta('program_sms', $program->sms_keyword?'':'13229966122');
                $lyMeta->setMeta('program_sms_keyword', $program->sms_keyword);
                $lyMeta->setMeta('program_email', $program->email);
                $lyMeta->setMeta('description_detail', $program->description);
            }
        });
    }
}
