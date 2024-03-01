<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\LtsMeta;
use App\Models\LtsItem;
use App\Jobs\InfluxQueue;
use Carbon\Carbon;
use App\Livewire\CreateSubmission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Laravel\Nova\Nova;
use App\Livewire\LyPulse;
// use Cookie;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pulse', LyPulse::class);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::mediaLibrary();
 
    Route::get('/file/submission', CreateSubmission::class);

    Route::get('/nova/switch/language', function (Request $request) {
        $key = 'preferred_language';
        $preferredLanguage = Cookie::get($key);

        $switchedLanguage = $preferredLanguage == 'en'?'zh':'en';
        Cookie::queue(cookie($key, $switchedLanguage));
        // Nova.success('Yey!');
            // en_US
            // $languageStr = $request->getPreferredLanguage();
            // $languageStr = 'zh_CN';
        // session()->flash('message', 'Something went wrong');
        return redirect()->back();
    });
});

Route::get('/storage/ly/corrections/{mp3}', function (Request $request, $mp3) {
    return redirect()->away("https://ly-pms2023.s3.ap-east-1.amazonaws.com/ly/corrections/$mp3");
});

Route::get('/storage/ly/audio/{year}/{code}/{day}.mp3', function (Request $request, $year, $code, $day) {
    $ymd = preg_replace('/\D+/', '', $day);
    $dt = Carbon::createFromFormat('ymd', $ymd);
    if(!auth()->id()){
        //√ hide if get 230930 when in 230926 in query. // then 403 if get mp3! @see routes/web.php
        if($dt > now()) return redirect(403); //403 Forbidden 
        
        // 未登录的人不可查看、收听 31天之外的节目, 但登录的主持人可以！
        if($dt->diffInDays(now()) > 31){//TODO Var 31 config("ly.max.show.days")=31
            // return redirect(401); // 401 Unauthorized
        }
    }

    $ip = $request->header('x-forwarded-for')??$request->ip();
    $domain =  config('pms.cloudfront_domain');
    $url = $request->url();
    $target = basename($url); //cc201221.mp3
    
    $tags = [];
    // TODO 一些直播的節目，直接使用官網的連結
    // if(in_array($tmpCode, ['cc','dy','gf'])){
    //     $domain =  'https://lpyy729.net';
    // }
    $tags['metric'] = 'lyOpen';
    $tags['host'] = $domain;
    $tags['keyword'] = $code;

    $fields = [];
    $fields['count'] = 1;
    $fields['target'] = $target;
    $fields['ip'] = $ip;

    $protocolLine = [
        'name' => 'click',
        'tags' => $tags,
        'fields' => $fields
    ];
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away("{$domain}/ly/audio/${year}/${code}/${day}.mp3");
});

// LTS audio
Route::get('/storage/ly/audio/{code}/{day}.mp3', function (Request $request, $code, $day) {
    $ip = $request->header('x-forwarded-for')??$request->ip();
    $domain =  'https://d3ml8yyp1h3hy5.cloudfront.net'; // TODO
    $url = $request->url();
    $target = basename($url); //cc201221.mp3
    
    $tags = [];
    $tags['metric'] = 'lyOpen';
    $tags['type'] = 'lts';
    $tags['host'] = $domain;
    $tags['keyword'] = $code;

    $fields = [];
    $fields['count'] = 1;
    $fields['target'] = $target;
    $fields['ip'] = $ip;

    $protocolLine = [
        'name' => 'click',
        'tags' => $tags,
        'fields' => $fields
    ];
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away("{$domain}/lts/${code}/${day}.mp3");
});


Route::get('/ip', function (Request $request) {
    $ip = $request->header('x-forwarded-for')??$request->ip();
    return [$ip,$request->ip()];
});

Route::get('/program/{lyMeta:code}', function (LyMeta $lyMeta) {
    // $isUnpublished '已下线，不可访问该播放列表'
    // 可以预先设置下线时间！
    if($lyMeta->unpublished_at && $lyMeta->unpublished_at < now()) abort(403);
    if($lyMeta->isLts){
        $playlist = $lyMeta->lts_items();
    }else{
        $playlist = $lyMeta->ly_items; 
    }    
    return view('program/playlist', compact('lyMeta', 'playlist'));
});

Route::get('/share/{hashId}', function ($hashId) {
    if(Str::startsWith($hashId, 'lts')){ //lts-item
        $item = LtsItem::findOrFail(LtsItem::keyFromHashId($hashId));
        $lyMeta = $item->lts_meta->ly_meta;
    }
    if(Str::startsWith($hashId, 'lyi')){ //ly-item
        $item = LyItem::findOrFail(LyItem::keyFromHashId($hashId));
        $lyMeta = $item->ly_meta;
    }
    
    $playlist = collect([$item]);
    return view('program/playlist', compact('lyMeta', 'playlist'));
})->name('share.lyItem');
