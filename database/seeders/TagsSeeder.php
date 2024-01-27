<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = [
            "良友" => "ly",
            "良院" => "lts",
        ];
        foreach ($category as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name, 'category');
            $slugs = '{"'.$locale.'":"category/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }

        $make = [
            "LY  制作中心" => "ly-center",
            "LTS 制作中心" => "lts-center",
            "版权赞助伙伴"  => "ly-company",
        ];
        foreach ($make as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name, 'make');
            $slugs = '{"'.$locale.'":"make/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }

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
            $slugs = '{"'.$locale.'":"ly/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }

        $ltsCategory = [
            "启航课程"=>"ltsnp",
            "本科文凭课程"=>"ltsdp",
            "进深文凭课程"=>"ltshdp",
            "专辑课程"=>"ltsnop",
        ];
        foreach ($ltsCategory as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'lts');
            $slugs = '{"'.$locale.'":"lts/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
        // 节目形式标题  节目形式代号
        $programFormat = [
            '独白分享' => 'monologue',
            '对话交谈' => 'dialogue',
            '访问对谈' => 'interview',
            '音乐分享' => 'music-sharing',
            '宣讲' => 'preach',
            '查经' => 'bible-study',
            '教学' => 'teaching',
            '资讯' => 'information',
            '广播剧' => 'radio-drama',
            '杂志综合' => 'magazine-style',
            '组合式' => 'modular',
            '综合休闲' => 'comprehensive-leisure',
            '其他' => 'other',
        ];
        foreach ($programFormat as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'program-format');
            $slugs = '{"'.$locale.'":"program-format/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }


        // 节目形式标题  节目形式代号
        $programFormat = [
            '独白分享' => 'monologue',
            '对话交谈' => 'dialogue',
            '访问对谈' => 'interview',
            '音乐分享' => 'music-sharing',
            '宣讲' => 'preach',
            '查经' => 'bible-study',
            '教学' => 'teaching',
            '资讯' => 'information',
            '广播剧' => 'radio-drama',
            '杂志综合' => 'magazine-style',
            '组合式' => 'modular',
            '综合休闲' => 'comprehensive-leisure',
            '其他' => 'other',
        ];
        foreach ($programFormat as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'program-format');
            $slugs = '{"'.$locale.'":"program-format/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
        
        // 节目性质标题  节目性质代号
        $programNature = [
            '福音性' => 'evangelistic',
            '栽培性' => 'nurturing',
            '训练性' => 'training',
        ];
        foreach ($programNature as $name => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'program-nature');
            $slugs = '{"'.$locale.'":"program-nature/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
        // 目标听众
        $targetAudience = [
            'age-group/child' => '儿童',
            'age-group/juvenile' => '少年',
            'age-group/youth' => '青年',
            'age-group/adult' => '成年',
            'age-group/elderly' => '老年',
            'group/family' => '家庭 ',
            'group/disabled' => '残疾人 ',
            'gender/male' => '男性',
            'gender/female' => '女性',
            'belief/non-believer' => '非信徒',
            'belief/catechumen' => '慕道者',
            'belief/believer' => '信徒',
            'belief/believer-leader' => '信徒领袖',
            'other' => '其他',
        ];
        foreach ($targetAudience as $slug => $name) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'target-audience');
            $slugs = '{"'.$locale.'":"target-audience/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
        $productionCentres = [
            '香港远东' => 'hk',
            '台湾远东' => 'tw',
            '美国远东' => 'us',
            '加拿大远东'=>'ca',
            'YS'=>'ys',
            '救恩之声' => 'vos',
            '广州工作室'=>'gz',
            '上海工作室'=>'sh',
            '宣道广播中心'=>'ar',
            '高飞扬传播'=>'gfy',
            "Believer's Ministry & Gospel" => 'bmg',
            '报佳音'=>'bjy',
            '新加坡远东'=>'sg',
        ];
        foreach ($productionCentres as $name  => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'production-centre');
            $slugs = '{"'.$locale.'":"production-centre/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }

        $sponsors = [
            "Believer's Ministry & Gospel"=>'bmg',
            'Haven Ministries'=>'hm',
            'In Touch Ministries'=> 'itm',
            'Leading the Way'=> 'ltw',
            'Thru the Bible' => 'ttb',
            '救恩之声'=>'vos',
            '宣道广播中心'=>'ar',
            '高飞扬传播'=>'gfy',
            '建道神学院'=>'abs',
            '海外基督使团'=>'omf',
        ];
        foreach ($sponsors as $name  => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'sponsor');
            $slugs = '{"'.$locale.'":"sponsor/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
        $programLanguages = [
            '普通话'=>'putonghua',
            '粤语'=>'cantonese',
            '英语'=>'english',
            '云南话'=>'yunnan',
            '康巴话'=>'kham',
            '维吾尔语'=>'uighur',
            '蒙古话'=>'mongolian',
            '壮语'=>'zhuang',
            '白族'=>'bai'
        ];
        foreach ($programLanguages as $name  => $slug) {
            $locale = app()->getLocale();
            $tag = Tag::findOrCreateFromString($name,'program-language');
            $slugs = '{"'.$locale.'":"program-language/'.$slug.'"}';//[$locale=>$slug];
            $sql = "update tags set slug = '{$slugs}' where id = {$tag->id}";
            DB::update($sql);
        }
    }
}
