<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Announcer;
use App\Models\LyMeta;
use Spatie\Tags\Tag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;
class LyMetaSeeder extends Seeder
{
    private $url = "https://www.729ly.net/program";
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::get($this->url);
        $dom = HtmlDomParser::str_get_html($response->body());

        foreach ($dom->find('.magazine-category') as $magazine){
            $categoryDom = $magazine->find('.magazine-category-title a', 0);
            $categoryTitle = $categoryDom->getAttribute('title');
            // $categoryUrl = $this->url . $categoryDom->getAttribute('href');
            // $categories[] = $category;
            // $categoryModel = Category::updateOrCreate(['name' => $categoryTitle]);
            $tag = Tag::findOrCreateFromString($categoryTitle, 'ly');
            // dd($categoryModel);
            foreach ($magazine->find('.magazine-item') as $item){
                $program = [];
                $program['name'] = $item->find('.page-header h2', 0)->text();

                
                // https://729ly.net/program/program-lifestyle-wisdom/program-hp
                $url = $this->url . $item->find('.magazine-item-media a', 0)->getAttribute('href');
                $tmpArray = explode('-', $url);
                $code = array_pop($tmpArray);

                // cover images
                // https://729lyprog.net/images/program_banners/bc_prog_banner.png
                // https://cdn.ly.yongbuzhixi.com/images/programs/hp_prog_banner_sq.jpg
                // $program['description'] = $item->find('.magazine-item-media img', 0)->getAttribute('data-src');
                
                $programAuthor = trim($item->find('.magazine-item-ct p', 0)->text());
                $programAuthor = str_replace('主持：', '', $programAuthor);
                $programAuthor = str_replace('；嘉宾：', '、', $programAuthor);
                $programAuthor = str_replace('；嘉宾讲员：', '、', $programAuthor);
                $programAuthor = str_replace('；嘉宾', '、', $programAuthor);
                $programAuthor = str_replace('良友圣经学院老师', '良院老师', $programAuthor);
                $programAuthor = str_replace('叶明道等', '叶明道', $programAuthor);
                $programAuthor = str_replace(" ", '', $programAuthor);

                // ltsdp ltshdp
                $program['code'] = $code;
                if(Str::endsWith($program['code'], 'dp')){
                    $program['code'] = $code . '1';
                    $this->save($program, $programAuthor, $tag);
                    $program['code'] = $code . '2';
                }
                $program['make_id'] = 1;
                $this->save($program, $programAuthor, $tag);

                // More about authors URL: 
                foreach ($item->find('.magazine-item-ct p a') as $item){
                    $name = trim($item->getAttribute('title'));
                    $description = $this->url . $item->getAttribute('href');
                    Announcer::where("name" , $name)->update(["description" => $description]);
                }
            }
        }
    }

    private function save($program, $programAuthor, $tag)
    {
        //withoutGlobalScopes()->
        $program['code'] = 'ma' . $program['code'];
        $programModel = LyMeta::firstOrCreate(['code'=> $program['code']], $program);
        if($programModel->wasRecentlyCreated){
            Log::info(__METHOD__, $program);
        }
        $programAuthors = explode('、', $programAuthor);

        $announcerModelIds = [];
        foreach ($programAuthors as $name){
            $announcerModel = false;
            if($name) $announcerModel = Announcer::firstOrCreate(["name" => $name]);
            // Announcer has programs <==> role has permissions
            if($announcerModel) $announcerModelIds[] = $announcerModel->id;
        }
        $programModel->announcers()->sync($announcerModelIds);
        // 更新 programModel 的 category_id
        $programModel->attachTag($tag);
        // $tag->programs()->save($programModel);
    }
}
