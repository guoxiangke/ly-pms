<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Jobs\InfluxQueue;
use Carbon\Carbon;
use App\Livewire\CreateSubmission;

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
});


Route::get('/storage/ly/audio/{year}/{code}/{day}.mp3', function (Request $request, $year, $code, $day) {
    $ymd = preg_replace('/\D+/', '', $day);
    $dt = Carbon::createFromFormat('ymd', $ymd);
    if(!$request->user){
        //√ hide if get 230930 when in 230926 in query. // then 403 if get mp3! @see routes/web.php
        if($dt > now()) return redirect(403); //403 Forbidden 
        
        // 未登录的人不可查看、收听 31天之外的节目, 但登录的主持人可以！
        if($dt->diffInDays(now()) > 31){//TODO Var 31 config("ly.max.show.days")=31
            // return redirect(401); // 401 Unauthorized
        }
    }

    //√ 新旧目录设计 对调： year/code -> code/year
    if (is_numeric($code)) {
        list($year, $code) = [$code, $year];
    }
    
    //is_old code
    if($dt <  Carbon::createFromFormat('Y-m-d', config('pms.launched_at'))){
        // $code = substr($code, 2);
        // $day = substr($day, 2);
    }
    // dd($code,$day);


    $ip = $request->header('x-forwarded-for')??$request->ip();
    $domain =  'https://d3ml8yyp1h3hy5.cloudfront.net'; // TODO
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

// LTS audio TODO : need test
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
    if($lyMeta->unpublished_at) abort(403);

    $playlist = $lyMeta->ly_items;
    return view('program/playlist', compact('lyMeta','playlist'));
});
Route::get('/share/{hashId}', function (LyMeta $lyMeta, $hashId) {
    $lyItem = LyItem::findOrFail(LyItem::keyFromHashId($hashId));
    $lyMeta = $lyItem->ly_meta;
    $playlist = collect([$lyItem]);
    return view('program/playlist', compact('lyMeta', 'playlist'));
})->name('share.lyItem');
