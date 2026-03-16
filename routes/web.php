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
use App\Models\Album;
use Laravel\Nova\Nova;
use App\Livewire\LyPulse;
// use App\Livewire\CustomerSearch;
// Route::get('/search', CustomerSearch::class)->name('search');
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
    return app()->isLocal()? view('welcome') : redirect()->route('nova.pages.home');
});

Route::get('/login', function () {
    return redirect()->route('nova.pages.home');
})->name('login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::mediaLibrary();

    Route::get('/file/submission', CreateSubmission::class)->name('upload');

    Route::get('/admin/pulse', LyPulse::class)->name('pulse');
});


Route::get('/ly/corrections/{version_folder}/{mp3}', function (Request $request, $version_folder, $mp3) {
    $domain =  config('pms.cloudfront_domain');
    return redirect()->away($domain."/ly/corrections/$version_folder/$mp3");
});
Route::get('/storage/ly/corrections/{version_folder}/{mp3}', function (Request $request, $version_folder, $mp3) {
    $domain =  config('pms.cloudfront_domain');
    return redirect()->away($domain."/ly/corrections/$version_folder/$mp3");
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
    $tags['metric'] = 'lyOpen';
    $tags['host'] = $domain;
    $tags['keyword'] = $code;

    $fields = [];
    $fields['count'] = 1;
    $fields['target'] = $target;
    $fields['ip'] = $ip;

    $protocolLine = [
        'name' => 'clicks',
        'tags' => $tags,
        'fields' => $fields
    ];
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away("{$domain}/ly/audio/${year}/${code}/${day}.mp3");
});

// LTS audio
Route::get('/storage/ly/audio/{code}/{day}.mp3', function (Request $request, $code, $day) {
    $ip = $request->header('x-forwarded-for')??$request->ip();
    $domain =  config('pms.cloudfront_domain');
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
        'name' => 'clicks',
        'tags' => $tags,
        'fields' => $fields
    ];
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away("{$domain}/lts/${code}/${day}.mp3");
});

Route::get('/program/{code}', function (Request $request, $code) {
    $lyMeta = lyMeta::where('code', $code)->first();

    if(!$lyMeta) $ltsMeta = ltsMeta::where('code', $code)->first();
    if(!$lyMeta && !$ltsMeta) abort(404);
    if($lyMeta){
        $order = $request->query('order')?'ASC':'DESC';
        // $isUnpublished '已下线，不可访问该播放列表'
        // 可以预先设置下线时间！
        if($lyMeta->unpublished_at && $lyMeta->unpublished_at < now()) abort(403);
        if($lyMeta->isLts){
            $playlist = $lyMeta->ltsItems($order)->get();
        }else{
            $playlist = $lyMeta->ly_items($order)->get();
        }
    }else{
        $order = $request->query('order')?'DESC':'ASC';
        if($order == 'DESC'){
            $playlist = $ltsMeta->lts_items($order)->get();
        }else{
            $playlist = $ltsMeta->lts_items_asc()->get();
        }
        $lyMeta = $ltsMeta;
    }

    $isShowContent = $lyMeta->isShowContent;
    return view('program/playlist', compact('lyMeta', 'playlist', 'order', 'isShowContent'));
})->name('playlist');

Route::get('/share/{hashId}', function ($hashId) {
    if(Str::startsWith($hashId, 'lts')){ //lts-item
        $item = LtsItem::with(['ltsMeta.ly_meta', 'contents.attachments'])->findOrFail(LtsItem::keyFromHashId($hashId));
        $lyMeta = $item->ltsMeta->ly_meta;
    }
    if(Str::startsWith($hashId, 'lyi')){ //ly-item
        $item = LyItem::with(['ly_meta', 'contents.attachments'])->findOrFail(LyItem::keyFromHashId($hashId));
        $lyMeta = $item->ly_meta;
    }

    $playlist = collect([$item]);
    $isShowContent = $lyMeta->isShowContent;
    return view('program/playlist', compact('lyMeta', 'playlist', 'isShowContent'));
})->name('share.lyItem');

Route::get('/albums', function () {
    $albums = Album::where('status', 'published')
        ->with('target.tags')
        ->get();

    // 获取所有 ly 分类标签
    $lyTags = \App\Models\Tag::where('type', 'ly')->orderBy('order_column')->get();

    // 判断是否年度专辑
    $isYearly = fn ($album) => (bool) preg_match('/\d{4}年$/', $album->name);

    // 排序函数
    $sortAlbums = function ($group) {
        return $group->sortByDesc(function ($album) {
            if (preg_match('/(\d{4})年$/', $album->name, $m)) {
                return '0_' . $m[1];
            }
            return '1_' . $album->created_at->format('YmdHis');
        })->values();
    };

    // 按节目分组，再分年度/非年度
    $buildGroups = function ($collection) use ($isYearly, $sortAlbums) {
        return $collection->groupBy(fn ($a) => $a->target?->name ?? '其他')
            ->map(function ($group) use ($isYearly, $sortAlbums) {
                $target = $group->first(fn ($a) => $a->target)?->target;
                $isArchived = $target && $target->end_at && $target->end_at <= now();
                return [
                    'cover' => $target?->cover,
                    'end_at' => $target?->end_at,
                    'archived' => $isArchived,
                    'tags' => $target?->tags->where('type', 'ly')->pluck('name')->toArray() ?? [],
                    'yearly' => $sortAlbums($group->filter($isYearly)),
                    'other' => $sortAlbums($group->reject($isYearly)),
                ];
            });
    };

    $allGroups = $buildGroups($albums);

    // 排序：在播按名称升序排前面，停播按 end_at 倒序排后面
    $allGroups = $allGroups->sortBy(function ($group, $name) {
        if ($group['archived']) {
            // 停播排后面，按 end_at 倒序（用 9999 减去时间戳）
            $ts = $group['end_at'] ? (9999999999 - strtotime($group['end_at'])) : 9999999999;
            return '1_' . str_pad($ts, 15, '0', STR_PAD_LEFT);
        }
        // 在播排前面，按名称升序
        return '0_' . $name;
    });

    return view('album.index', compact('allGroups', 'lyTags'));
})->name('albums.index');

Route::get('/album/{hashId}', function ($hashId) {
    $album = Album::findOrFail(Album::keyFromHashId($hashId));

    if ($album->status !== 'published') abort(404);

    $playlist = $album->getPlaylistItems();

    return view('album.playlist', compact('album', 'playlist'));
})->name('album.show');
