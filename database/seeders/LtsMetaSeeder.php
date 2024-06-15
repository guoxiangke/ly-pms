<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\LtsMeta;
use Spatie\Tags\Tag;
use Illuminate\Support\Arr;

class LtsMetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = [
          0 => '启航课程',
          1 => '本科文凭课程',
          2 => '进深文凭课程',
          3 => '专辑课程',
        ];

        $json = Http::get("https://wechat.yongbuzhixi.com/api/lts33")->json();
        foreach ($json as $key => $item) {
            // @see App\Models\LtsMeta::code()
            $item['code'] =  'ma' . $item['code'];

            $filter = Arr::only($item, ['name', 'description', 'avatar', 'code', 'count','author','index']);
            $filter['wx_index'] = $filter['index'];  // change index => wx_index!
            unset($filter['index']);
            $ltsMeta = LtsMeta::Create($filter);//compact('code'), 
            $categoryTitle = $category[$item['category']];
            $tag = Tag::findOrCreateFromString($categoryTitle, 'lts');
            $ltsMeta->attachTag($tag);
            Log::info(__METHOD__, [$categoryTitle, $filter]);
         }
    }
}
