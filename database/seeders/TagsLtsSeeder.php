<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;
use Illuminate\Support\Facades\DB;

class TagsLtsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lyCategory = [
            "启航课程"=>"ltsnp",
            "本科文凭课程"=>"ltsdp",
            "进深文凭课程"=>"ltshdp",
            "专辑课程" => "ltsnop",
        ];
        foreach ($lyCategory as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'lts');
            $slugs = '{"'.$locale.'":"'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
    }
}
