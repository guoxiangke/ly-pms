<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;
use Illuminate\Support\Facades\DB;

class TagsLySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lyCategory = [
            "生活智慧"=>"lifestyle-wisdom",
            "少儿家庭"=>"kids-family",
            "诗歌音乐"=>"songs-music",
            "生命成长"=>"life-grow",
            "圣经讲解"=>"bible-explain",
            "课程训练"=>"course-training",
            "其他语言"=>"minority",
            "粤语节目"=>"other-cantonese"
        ];
        foreach ($lyCategory as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'ly');
            $slugs = '{"'.$locale.'":"'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
    }
}
