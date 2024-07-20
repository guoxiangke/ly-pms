<?php

namespace App\Livewire;

use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\User;
use Livewire\Component;
use Spatie\MediaLibraryPro\Livewire\Concerns\WithMedia;
use Spatie\MediaLibraryPro\Models\TemporaryUpload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Observers\LyItemObserver;
use getID3;
use App\Jobs\WriteID3TagAndSync2S3Queue;
class CreateSubmission extends Component
{   
    use WithMedia;

    public $user;

    public $title = 'Submission';
    public $hasNewFile = false;
    public $messageTitle = "";
    public $message = '';

    // [Rule('required', as:'da title', message: '必须上传文件')]
    // [Rule(['files.*' => 'file|max:1024'])]
    public $files = [];


    public function mount()
    {
        $currentLocale = app()->getLocale();
        app()->setLocale('zh');
        // dd($currentLocale);
        $this->user = auth()->user()??User::find(1);
        $path = route('nova.pages.index', 'ly-items');
        $this->message = "上传节目音频注意：<br/>1.本页面只供提交未上传过的节目音频。如节目音频早前已上传，但需要更换，请使用中文事工办公室指定的其他方式。<br/>2. 可以同时上传多个节目音频，惟不可超出音频大小上限。<br/>3. 节目音频格式须为64 kbps、48 kHz、mono、mp3。<br/>4. 节目音频档名须为xxxyymmdd.mp3（xxx为节目代号，yymmdd为播出日期的年年月月日日；良院或指定节目除外）。<br/>5. 节目音频和有关的节目简介，须同时提交。<br/>6. 提交节目简介后如需要修改，请发电邮到program@liangyou.net通知中文事工办公室同工。";
    }


    public function submit()
    {
        // if alias in [arleay configed alieas]!
        $this->message = '';


        $fileNames = [];
        $rules = [];
        $messages = [
            'regex' => ':attribute 档名格式错误。',//The :attribute field Name is not match ma?+code+yymmdd.mp3
            'unqiue' => '第 :attribute 个文件，早前已经上传',//The :attribute field is already exsits!
        ];
        $descriptions = [];
        $aliases = [];
        $lyMetaIds = [];

        // 0. name 要是 macc230929.mp3
        // 1. 名字 要唯一！之前没有上传过！
        $count = 0;
        foreach ($this->files as $key => $file) {
            $key = 'files_'.++$count;
            if(!isset($file['oldUuid'])) continue; //只处理新文件！
            if(isset($file['oldUuid'])){
                $this->hasNewFile = true;
                // this is a newly added file
                $fileNames[$key] = $file['name'];
                // must be mamw230101.mp3
                // starts_with:ma|ends_with:.mp3|2+|23mmdd
                $rules[$key] = 'regex:/[a-zA-Z]{2,}\d{6}\.mp3$/';
                $sizes[$key] = $file['size'];
                $fileNames[$key] = $file['file_name'];
            }
            // 验证命名规则
            $validator = Validator::make($fileNames, $rules, $messages);//->validate();
            if ($validator->fails()) {
                $this->messageTitle = "注意：{$fileNames[$key]} 存在以下错误！";
            }

            $alias = explode('.', $file['name'])[0];
            $aliases[$key] = $alias;
            $descriptions[$key] = $file['custom_properties']['description']??'';

            // 验证code ma?
            $code = preg_filter('/\d/', '', $alias);
            $lyMeta = LyMeta::active()->whereCode($code)->first();
            if(!$lyMeta){
                return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "第{$count}个文件 系统内未有相关代号的记录。"))->validate();
            }
            $lyMetaIds[$key] = $lyMeta->id;


            // 验证唯一性：请修改文件名！ // 'required|unqiue:App\Models\LyItem,alias';
            $validator = Validator::make(['alias' => $alias], ['alias' => Rule::unique('ly_items')], $messages);
            if ($validator->fails()) {
                $this->messageTitle = "注意：{$fileNames[$key]} 存在以下错误！";
                $this->message = "第{$count}个文件 早前已经上传。 <br/>请检查档名是否有误，修改后再上传。";
            }
            $validated = $validator->validated();
        }
        Validator::make($fileNames, $rules, $messages)->validate();
        // get mp3 长度？
        $count = 0;
        $playtime_strings = [];
        $filesizes = [];
        foreach ($this->files as $key => $file) {
            $key = 'files_'.++$count;
            if(!isset($file['oldUuid'])) continue; //只处理新文件！
            // if(isset($file['oldUuid']))
            {
                $tempFilePath = TemporaryUpload::findByMediaUuid($file['uuid'])->getFirstMedia()->getPath();
                $getID3 = new getID3;
                $thisFileInfo = $getID3->analyze($tempFilePath);
                // dd($thisFileInfo);
                $playtime_strings[$key] = $thisFileInfo['playtime_string'];
                $filesizes[$key] = $thisFileInfo['filesize'];

                if(isset($thisFileInfo['comments']['picture'][0])) {
                    // https://annissimo.com/how-to-throw-validationexception-in-laravel-without-request-validation-helpers-or-manually-creating-a-validator
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "The $key field 已经有图片了！")//不再处理了.
                    )->validate();
                }
                if($thisFileInfo['audio']['channelmode'] != 'mono') {
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "第{$count}个文件  音频并非单声道（mono）。")
                    )->validate();
                }
                if($thisFileInfo['audio']['sample_rate'] != 48000) {
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "第{$count}个文件 音频并非48 kHz。")
                    )->validate();
                }
                // "bitrate" => 64000.872433818
                if($thisFileInfo['audio']['bitrate'] != 64000) {
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "第{$count}个文件 音频是 {$thisFileInfo['audio']['bitrate']} 并非 64 kbps。")
                    )->validate();
                }
                // add to queue
                WriteID3TagAndSync2S3Queue::dispatch($tempFilePath, $descriptions[$key]);
            }
        }

        // ✅验证通过后，更新 Description！  save description => lyItem
        $links = "\n<ol style='list-style:decimal'>";
        foreach ($aliases as $key => $alias) {
            $ymd = preg_replace('/\D+/', '', $alias) . " 00:00:00";
            $play_at = Carbon::createFromFormat('ymd H:i:s', $ymd);
            $lyItem = LyItem::firstOrCreate(['alias'=>$alias],[
                'ly_meta_id' => $lyMetaIds[$key],
                'playtime_string' => $playtime_strings[$key],
                'filesize' => $filesizes[$key],
                'description' => $descriptions[$key]??'',
                'play_at' => $play_at,
            ]);
            $links .="\n<li><a target='_blank' href='" . route('nova.pages.detail',['resource'=>'ly-items','resourceId'=>$lyItem->id]) . "'>$fileNames[$key]</a></li>";
        }
        $links .= "</ol>";
        // $fileSubmission
        //     ->addFromMediaLibraryRequest($this->files)
        //     ->withCustomProperties('extra_field')
        //     ->toMediaCollection('mp3');
        $this->messageTitle = "成功提交".count($aliases)."个音频，如需继续提交，请刷新页面。";
        $this->message .= $links;
    }

    public function render()
    {
        return view('livewire.create-submission')
            ->layout('layouts.submission');
    }
}
