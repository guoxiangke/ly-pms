<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Jobs\InfluxQueue;
use Carbon\Carbon;

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
    $domain =  'https://d3ml8yyp1h3hy5.cloudfront.net'; //TODO config
    $url = $request->url();
    $target = basename($url); //cc201221.mp3
    
    $tags = [];
    // 一些直播的節目，直接使用官網的連結
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
    $domain =  'https://d3ml8yyp1h3hy5.cloudfront.net';
    // $domain =  'https://729lyprog.net';
    // $domain =  'https://txly2.net';
    // https://lpyy729.net
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
        'name' => 'click2',
        'tags' => $tags,
        'fields' => $fields
    ];
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away("{$domain}/lts/${code}/${day}.mp3");
});

// http://127.0.0.1:8000/redirect?target=https://*.com/@fwdforward/7XFVL5o.m4a?metric=connect%26category=601%26bot=4
// metric:默认是connect 收听/看/点击链接
// by：author 可选 %26author=@fwdforward
Route::get('/redirect', function (Request $request) {
    $url = $request->query('target');
    $status = 302;
    $headers = ['referer' => $url];
    // $ip = $request->header('x-forwarded-for')??$request->ip();
    $ip = $request->ip();
    $parts = parse_url($url); //$parts['host']
    // $paths = pathinfo($url); //mp3
    $url = strtok($url, '?'); //remove ?q=xxx
    // Header may not contain more than a single header, new line detected
    $url=str_replace(PHP_EOL, '', $url);
    $url=str_replace('%0A', '', $url);// url encode /n
    
    $target = basename($url); //cc201221.mp3
    
    $tags = [];
    if(isset($parts['query'])) parse_str($parts['query'], $tags);
    $tags['host'] = $parts['host'];
    // measurement/metric
    // $tags = http_build_query($data, '', ',');// category=603,bot=4    

    $fields = [];
    $fields['count'] = 1;
    $fields['target'] = $target;
    $fields['ip'] = $ip;
    // $fields = http_build_query($fields, '', ',');// category=603,bot=4
    
    // 原始获取人！
    // $url .= '%26to='.$to; //unset(to) => Field[to]=wxid;
    if(isset($tags['to'])) {
        $fields['to'] = $tags['to'];
        unset($tags['to']);
    }
    // ?_=1
    if(isset($tags['_'])) unset($tags['_']);

    $protocolLine = [
        'name' => 'clicks', //action=click/listen/view/tap
        'tags' => $tags,
        'fields' => $fields
    ];
    // dd($protocolLine, $url, $status, $headers);
    // $protocolLine = $metric.$tags.' count=1i,target="'.$target.'",ip="'.$ip.'"';
    // ly-listen,category=603,bot=%E5%8F%8B4count=1i,target="ee230909.mp3"
    // TODO Statistics BY IP / BY target.
    // dd($protocolLine,$parts,$url,$ip);
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away($url, $status, $headers);
});

Route::get('/ip', function (Request $request) {
    $ip = $request->header('x-forwarded-for')??$request->ip();
    return [$ip,$request->ip()];
});

Route::mediaLibrary();
use App\Http\Controllers\FileSubmissionController;
Route::get('collection', [FileSubmissionController::class, 'create'])->name('blade.collection');
Route::post('collection', [FileSubmissionController::class, 'store']);

Route::get('/program/{lyMeta:code}', function (LyMeta $lyMeta) {
    // dd($lyMeta->toArray(),$lyMeta->ly_items->first()->path);
    $playlist = $lyMeta->ly_items;
    return view('program/playlist', compact('lyMeta','playlist'));
});
Route::get('/share/{hashId}', function (LyMeta $lyMeta, $hashId) {
    $lyItem = LyItem::findOrFail(LyItem::keyFromHashId($hashId));
    $lyMeta = $lyItem->ly_meta;
    $playlist = collect([$lyItem]);
    return view('program/playlist', compact('lyMeta', 'playlist'));
})->name('share.lyItem');
