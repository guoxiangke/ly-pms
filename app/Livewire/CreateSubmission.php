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

class CreateSubmission extends Component
{   
    use WithMedia;

    public $user;

    public $hasNewFile = false;
    public $messageTitle = "注意：上传请谨慎";
    public $message = '本上传功能只验证新提交的文件。<br/>1. 文件一旦SUBMIT(提交)，将进入处理队列，不可更改（修改删除无效）<br/>2. 已提交的文件(SUBMIT)不可更改Name，不要轻易改动Description,<a href="/nova/resources/ly-items"><s>想改动?</s></a><br/>3. 当天可追加提交(再次提交请先刷新本页)';

    // [Rule('required', as:'da title', message: '必须上传文件')]
    // [Rule(['files.*' => 'file|max:1024'])]
    public $files = [];


    public function mount()
    {
        $this->user = auth()->user()??User::find(1);
    }


    public function submit()
    {
        // if alias in [arleay configed alieas]!
        $this->message = '';


        $fileNames = [];
        $rules = [];
        $messages = [
            'regex' => 'The :attribute field Name is not match ma?+code+yymmdd.mp3',
            'unqiue' => 'The :attribute field is already exsits!',
        ];
        $descriptions = [];
        $aliases = [];
        $lyMetaIds = [];

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
                return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "The $key field 命名前缀不存在！"))->validate();
            }
            $lyMetaIds[$key] = $lyMeta->id;


            // 验证唯一性：请修改文件名！ // 'required|unqiue:App\Models\LyItem,alias';
            $validator = Validator::make(['alias' => $alias], ['alias' => Rule::unique('ly_items')], $messages);
            if ($validator->fails()) {
                $this->messageTitle = "注意：{$fileNames[$key]} 存在以下错误！";
                $this->message = "The $key field is already exsits! <br/>Please change the Name(alias with .mp3) or rePlace & reSubmit!";
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
                $playtime_strings[$key] = $thisFileInfo['playtime_string'];
                $filesizes[$key] = $thisFileInfo['filesize'];

                if(isset($thisFileInfo['comments']['picture'][0])) {
                    // https://annissimo.com/how-to-throw-validationexception-in-laravel-without-request-validation-helpers-or-manually-creating-a-validator
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "The $key field 已经有图片了！")//不再处理了.
                    )->validate();
                }
                if($thisFileInfo['audio']['channelmode'] != 'mono') {
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "The $key field 不是单声道！")
                    )->validate();
                }
                if($thisFileInfo['audio']['sample_rate'] != 48000) {
                    return Validator::make([], [])->after(fn ($validator) => $validator->errors()->add('some_error', "The $key field sample_rate不是48000！")
                    )->validate();
                }

                LyItemObserver::writeID3Tag($tempFilePath, $descriptions[$key]);
            }
        }

        // ✅验证通过后，更新 Description！  save description => lyItem
        $links = "\n<ol>";
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
