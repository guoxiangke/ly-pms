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
        $category= [
          0 => '启航课程',
          1 => '本科文凭课程',
          2 => '进深文凭课程',
          3 => '专辑课程',
        ];

        $json = Http::get("https://wechat.yongbuzhixi.com/api/lts33")->json();
        foreach ($json as $key => $item) {
            $code = $item['code'];
            Log::error(__CLASS__, [$item['code']]);
            // unset($item['image']);

            $filter = Arr::only($item, ['name', 'description', 'avatar', 'code', 'count','author','index']);
            $model = LtsMeta::Create($filter);//compact('code'), 
            $categoryTitle = $category[$item['category']];
            $tag = Tag::findOrCreateFromString($categoryTitle, 'lts');
            $model->attachTag($tag);
         }
    }
}
