<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Make;
use App\Models\Tag;

class MakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $makers = [
            // 制作中心 Center
            "HK香港远东",
            "Soooradio",
            "TW台湾远东",
            "US美国远东",
            "CA加拿大远东",
            "GZ广州工作室",//广州
            "SH上海工作室",//上海
            "*! 报佳音",

            // 制作中心 && 赞助伙伴
            "!* Believer's Ministry & Gospel",
            "!* 宣道广播中心",
            "!* 高飞扬传播",
            "!* 救恩之声",

            // LTS maker
            "** PH",//菲律宾/马尼拉 Philippines/Manila
            "** SG",//Singapore


            // 赞助伙伴 company
            "!! Haven Ministries",
            "!! In Touch Ministries",
            "!! Leading the Way",
            "!! Thru the bible",
            "!! 建道神学院",
            "!! 海外基督使团",
        ];
        foreach ($makers as $maker) {
            $make = Make::firstOrCreate(['name'=>$maker]);
            
            // $makes = [
            //     "LY  制作中心" => "ly-center",
            //     "LTS 制作中心" => "lts-center",
            //     "版权赞助伙伴"  => "ly-company",
            // ];

            $categoryTitle = "LY  制作中心";
            if(Str::startsWith($maker, '**')) $categoryTitle = "LTS 制作中心";
            if(Str::startsWith($maker, '!!')) $categoryTitle = "版权赞助伙伴";
            $tag = Tag::findOrCreateFromString($categoryTitle, 'make');
            $make->attachTag($tag);
            if(Str::startsWith($maker, '!*')) { // 2 tags
                $tag1 = Tag::findOrCreateFromString('版权赞助伙伴', 'make');
                $tag2 = Tag::findOrCreateFromString('LY  制作中心', 'make');
                $make->attachTags([$tag1,$tag2]);
            }
        }
    }
}
