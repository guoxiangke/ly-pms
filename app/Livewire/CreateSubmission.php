<?php

namespace App\Livewire;

use App\Models\FileSubmission;
use App\Models\LyMeta;
use App\Models\LyItem;
use Livewire\Component;
use Spatie\MediaLibraryPro\Livewire\Concerns\WithMedia;
use Spatie\MediaLibraryPro\Models\TemporaryUpload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use getID3;
use getid3_writetags;

class CreateSubmission extends Component
{   
    use WithMedia;

    public $user;

    public $dateString;

    public $hasNewFile = false;
    public $messageTitle = "注意：上传请谨慎";
    public $message = '本上传功能只验证新提交的文件。<br/>1. 文件一旦SUBMIT(提交)，将进入处理队列，不可更改（修改删除无效）<br/>2. 已提交的文件(SUBMIT)不可更改Name，不要轻易改动Description,<a href="/nova/resources/ly-items"><s>想改动?</s></a><br/>3. 当天可追加提交';

    #[Rule('required', as:'da title', message: '必须上传文件')]
    #[Rule(['files.*' => 'file|max:1024'])]
    public $files = [];

    public $fileSubmission;

    public function mount()
    {
        $user = auth()->user();
        $this->user = $user;

        $dateString = now()->format('ymd');
        $this->dateString = $dateString;

        $fileSubmission = FileSubmission::firstOrCreate([
            'user_id' => $user->id,
            'generated_at' => now()->copy()->startOfDay(),
        ]);
        
        $this->fileSubmission = $fileSubmission;
        // $this->addError('message', 'The email field is invalid.');
    }


    public function submit()
    {
        $this->message = '';

        $fileSubmission = $this->fileSubmission;

        $inputs = [];
        $rules = [];
        $messages = [
            'regex' => 'The :attribute field Name is not match ma+code+yymmdd.mp3',
            'unqiue' => 'The :attribute field is already exsits!',
        ];
        $descriptions = [];
        $aliases = [];

        // 0. name 要是 macc230929.mp3
        // 1. 名字 要唯一！之前没有上传过！
        $count = 0;
        foreach ($this->files as $key => $file) {
            // dd($this->files);
            $key = 'files_'.++$count;
            if(!isset($file['oldUuid'])) continue; //只处理新文件！
            if(isset($file['oldUuid'])){
                $this->hasNewFile = true;
                // this is a newly added file
                $inputs[$key] = $file['name'];
                // must be mamw230101.mp3
                // starts_with:ma|ends_with:.mp3|2+|23mmdd
                $rules[$key] = 'regex:/^ma[a-zA-Z]{2,}\d{6}\.mp3$/';
                $sizes[$key] = $file['size'];
                $fileNames[$key] = $file['file_name'];
            }
            // 验证命名规则
            $validator = Validator::make($inputs, $rules, $messages);//->validate();
            if ($validator->fails()) {
                $this->messageTitle = "注意：{$inputs[$key]} 存在以下错误！";
            }

            $alias = explode('.', $file['name'])[0];
            $aliases[$key] = $alias;
            $descriptions[$key] = $file['custom_properties']['description']??'';

            // 验证唯一性：请修改文件名！ // 'required|unqiue:App\Models\LyItem,alias';
            $validator = Validator::make(['alias' => $alias], ['alias' => Rule::unique('ly_items')], $messages);
            if ($validator->fails()) {
                $this->messageTitle = "注意：{$inputs[$key]} 存在以下错误！";
                $this->message = "The $key field is already exsits! <br/>Please change the Name(alias with .mp3) or rePlace & reSubmit!";
            }
            $validated = $validator->validated();
        }
        Validator::make($inputs, $rules, $messages)->validate();
        // dd($aliases, $this->files);
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
                // "fileformat" => "mp3"
                // "filesize" => 28481497
                // "playtime_seconds" => 3560.184
                // "playtime_string" => "59:20"
                // "audio"["channels"] => 1
                $playtime_strings[$key] = $thisFileInfo['playtime_string'];
                $filesizes[$key] = $thisFileInfo['filesize'];

                // 验证，必须 64000 48000 mono
                // "bitrate" => 64000
                // "audio"["sample_rate"] => 48000
                // "audio"["channelmode"] => mono

                if(isset($thisFileInfo['comments']['picture'][0])) {
                    // https://annissimo.com/how-to-throw-validationexception-in-laravel-without-request-validation-helpers-or-manually-creating-a-validator
                    return Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('some_error', "The $key field 已经有图片了！");//不再处理了.
                    })->validate();
                }
                // file_put_contents('/tmp/ly.png', $thisFileInfo['comments']['picture'][0]['data']);
                if($thisFileInfo['audio']['channelmode'] != 'mono') {
                    return Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('some_error', "The $key field 不是单声道！");
                    })->validate();
                }
                if($thisFileInfo['audio']['sample_rate'] != 48000) {
                    return Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('some_error', "The $key field sample_rate不是48000！");
                    })->validate();
                }
                if($thisFileInfo['audio']['bitrate'] != 64000) {
                    return Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('some_error', "The $key field bitrate不是64000！");
                    })->validate();
                }

                // https://github.com/JamesHeinrich/getID3/issues/422
                $path = public_path('logo.png');
                $TagData['attached_picture'][0] = [
                    'data' => file_get_contents($path),
                    'picturetypeid' => 3,
                    'description' => 'Liangyou.png',
                    'mime' => 'image/png',
                ];

                $tagwriter = new getid3_writetags;
                $tagwriter->filename       = $tempFilePath;
                $tagwriter->tagformats     = ['id3v2.3'];//$TagFormatsToWrite;//id3v2.4
                // $tagwriter->overwrite_tags = false;
                $tagwriter->tag_encoding   = 'UTF-8';

                
                $alias = $aliases[$key]; // "mattb250110";
                $pattern = '/^(\D+)(\d+)/';
                preg_match($pattern, $alias, $matches);
                $code = $matches[1];//mattb
                $date = $matches[2];//250110
                $lyMeta = LyMeta::whereCode($code)->firstOrFail();
                $lyMetaTitle = $lyMeta->name;
                $year = substr(date('Y'), 0, 2) . substr($date, 0, 2);//20 + 23/24 =》 2023->2024
                $dataStr = substr(date('Y'), 0, 2) . $date;

                $TagData['title'][]   = "$lyMetaTitle-$dataStr";
                $TagData['copyright_message'][]   = "©良友电台";
                $TagData['album'][]   = $lyMetaTitle;//"穿越圣经";
                $TagData['year'][]    = $year;//"2024";
                $TagData['comment'][] = $descriptions[$key];
                $tagwriter->tag_data = $TagData;
                $tagwriter->WriteTags();
                if($errors = $tagwriter->errors){
                    return Validator::make([], [])->after(function ($validator) use($errors) {
                        $validator->errors()->add('some_error', $errors);
                    })->validate();
                }
                //TODO 移动copy文件到s3！
            }
        }

        // ✅验证通过后，更新 Description！  save description => lyItem
        $links = '';
        foreach ($aliases as $key => $value) {
            $lyItem = LyItem::firstOrCreate(['alias'=>$alias],[
                'playtime_string' => $playtime_strings[$key],
                'filesize' => $filesizes[$key],
            ]);
            $description = $descriptions[$key];
            if($description && $lyItem->description != $description) {
                $lyItem->update(compact('description'));
            }
            $links .="\n" . route('nova.pages.detail',['resource'=>'ly-items','resourceId'=>$lyItem->id]);
        }

        $fileSubmission
            ->addFromMediaLibraryRequest($this->files)
            ->withCustomProperties('extra_field')
            ->toMediaCollection('mp3');
        $this->messageTitle = "成功提交".count($aliases)."条记录，谢谢！";
        $this->message = 'Your form has been submitted.'.$links;
    }

    public function render()
    {
        return view('livewire.create-submission');
        // default layout file : components.layouts.app
        // ->layout('layouts.submission'); //views/layouts/submission.blade.php
    }
}
