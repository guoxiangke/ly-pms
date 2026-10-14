<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nuwave\Lighthouse\GraphQL;
// use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Execution\ContextFactory;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\Content;
use App\Models\Album;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
// use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->post('/ly_items', function (Request $request) {
    $user = $request->user();
    if($user->id !== 2) return abort(403, 'Unauthorized action.');
    $alias = $request->input('alias');

    $code = preg_replace('/[^a-zA-Z]/', '', $alias);
    $lyMeta = LyMeta::where(['code'=>$code])->firstOrFail();

    $data = $request->only(['alias', 'description', 'program_station_code']);
    $data['ly_meta_id'] = $lyMeta->id;
    $lyItem = LyItem::firstOrCreate(['alias'=>$alias], $data);
    
    if($lyItem->wasRecentlyCreated) return ['success'];

    if($request->input('description')){
      $lyItem->update($request->only(['description']));
    }
    if($request->input('program_station_code')){
      $lyItem->update($request->only(['program_station_code']));
    }
    return ['success'];
});


Route::middleware('auth:sanctum')->post('/contents', function (Request $request) {
    $user = $request->user();
    if($user->id !== 2) return abort(403, 'Unauthorized action.');
    $alias = $request->input('alias');// mw251002

    $code = preg_replace('/[^a-zA-Z]/', '', $alias);
    $lyMeta = LyMeta::where(['code'=>$code])->firstOrFail();

    $newBody = $request->input('content');
    // 3. 移除 {attachments} 标签
    $newBody = preg_replace('/\{attachments\}/', '', $newBody);
    // 4. 移除空的 <p> 标签
    $newBody = preg_replace('/<p>\s*<\/p>/', '', $newBody);
    // 5. 清理多余的换行符和空白字符
    $newBody = preg_replace('/\r\n\s*\r\n/', "\r\n", $newBody);
    $newBody = trim($newBody);
    $newTitle = $request->input('title');

    $data['description'] = $newTitle;
    $data['ly_meta_id'] = $lyMeta->id;
    $lyItem = LyItem::firstOrCreate(['alias'=>$alias], $data);

    // 如果已经存在（有且仅有1个 contents）
    $content = $lyItem->contents()->first();
    if ($content) {
        $content->update([
            'title' => $newTitle,
            'body' => $newBody,
        ]);
    } else {
        $content = $lyItem->contents()->create([
            'title' => $newTitle,
            'body' => $newBody,
            'user_id' => 2,
        ]);
    }
    Log::info('lyItem: ' . $alias . ' - ' . $content->id);
    return ['success'];
});

Route::get('/categories', function (Request $request) {
    $query = <<<GQL
        {
          data:tags_by_type(withType:"ly"){
            id
            name
            type
            order_column
            programs:ly_metas{
              id
              name
              alias:code
              avatar:cover
              description
              begin_at
              end_at
            }
          }
        }
    GQL;
    $graphQL = app(GraphQL::class);
    $createsContext = app(ContextFactory::class);
    $context = $createsContext->generate($request);
    $result = $graphQL->executeQueryString($query, $context);
    // pop last one: 粤语节目
    $data = $result['data']['data'];
    array_pop($data);
    return ['data' => $data];
});
Route::get('/programs', function (Request $request) {
    $query = <<<GQL
        {
          data:ly_metas{
            id
            name
            avatar:cover
            category
            alias:code
            begin_at
            end_at
            description
            announcers{
                id
                name
                avatar
                birthday
                description
                begin_at
                stop_at
                
            }
          }
        }
    GQL;
    $graphQL = app(GraphQL::class);
    $createsContext = app(ContextFactory::class);
    $context = $createsContext->generate($request);
    $result = $graphQL->executeQueryString($query, $context);
    return ['data' => $result['data']['data']];
});

Route::get('/today', function (Request $request) {
  $now = date('Y-m-d');
  $query = <<<GQL
    {
      ly_items(play_at: "$now 00:00:00") {
        data {
          id
          description
          alias
          play_at
          path: novaMp3Path
          link: path
          program: ly_meta {
            id
            name
            code
          }
        }
      }
    }
  GQL;
  $graphQL = app(GraphQL::class);
  $createsContext = app(ContextFactory::class);
  $context = $createsContext->generate($request);
  $result = $graphQL->executeQueryString($query, $context);
  $data1 = $result['data']['ly_items']['data'];

  $query = <<<GQL
    {
      lts_items(play_at: "$now 00:00:00") {
        data {
          id
          description
          alias
          play_at
          path: novaMp3Path
          link: path
          program: ly_meta {
            id
            name
            code
          }
        }
      }
    }
  GQL;
  $graphQL = app(GraphQL::class);
  $createsContext = app(ContextFactory::class);
  $context = $createsContext->generate($request);
  $result = $graphQL->executeQueryString($query, $context);
  $data2 = $result['data']['lts_items']['data'];

  return ['data' => collect(array_merge($data1,$data2))->shuffle()];
});

// 专辑列表：按节目分组，结构与 /albums 网页版一致
Route::get('/albums', function () {
    // clips.lyItems.ly_meta 供 Manual（片段型）专辑反查归属节目，见 Album::resolvedTarget()
    $query = Album::with(['target.tags', 'clips.lyItems.ly_meta.tags']);
    if (auth()->id() !== 1) {
        // $query->published();
    }
    $albums = $query->get();
    // dd($albums);

    // 所有 ly 分类标签
    $lyTags = Tag::where('type', 'ly')->orderBy('order_column')->get()
        ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'order_column' => $t->order_column])
        ->values();

    // 单个专辑的精简结构
    $mapAlbum = fn (Album $album) => [
        'hash_id' => $album->hashId,
        'name' => $album->name,
        'description' => $album->description,
        'cover' => $album->cover_url,
        'is_rrule_mode' => $album->is_rrule_mode,
        'created_at' => $album->created_at?->format('Y-m-d H:i:s'),
    ];

    // 是否年度专辑（名称以 XXXX年 结尾）
    $isYearly = fn (Album $album) => (bool) preg_match('/\d{4}年$/', $album->name);

    // 组内排序：年度按年份倒序，其余按创建时间倒序
    $sortAlbums = fn ($group) => $group->sortByDesc(function (Album $album) {
        if (preg_match('/(\d{4})年$/', $album->name, $m)) {
            return '0_' . $m[1];
        }
        return '1_' . $album->created_at->format('YmdHis');
    })->values();

    // 按节目名分组
    $groups = $albums->groupBy(fn (Album $a) => $a->resolvedTarget()?->name ?? '其他')
        ->map(function ($group, $name) use ($isYearly, $sortAlbums, $mapAlbum) {
            $target = $group->map(fn (Album $a) => $a->resolvedTarget())->filter()->first();
            $isArchived = $target && $target->end_at && $target->end_at <= now();
            return [
                'name' => $name,
                'cover' => $target?->cover,
                'end_at' => $target?->end_at?->format('Y-m-d'),
                'archived' => $isArchived,
                'tags' => $target?->tags->where('type', 'ly')->pluck('name')->values()->toArray() ?? [],
                'yearly' => $sortAlbums($group->filter($isYearly))->map($mapAlbum)->values(),
                'other' => $sortAlbums($group->reject($isYearly))->map($mapAlbum)->values(),
            ];
        });

    // 在播按名称升序排前，停播按 end_at 倒序排后
    $groups = $groups->sortBy(function ($group, $name) {
        if ($group['archived']) {
            $ts = $group['end_at'] ? (9999999999 - strtotime($group['end_at'])) : 9999999999;
            return '1_' . str_pad($ts, 15, '0', STR_PAD_LEFT);
        }
        return '0_' . $name;
    })->values();

    return ['data' => $groups, 'tags' => $lyTags];
});

// 专辑详情 + 播放列表
Route::get('/album/{hashId}', function ($hashId) {
    $album = Album::findOrFail(Album::keyFromHashId($hashId));

    if (! $album->published_at && auth()->id() !== 1) abort(404);

    $playlist = $album->getPlaylistItems()->map(fn ($item) => array_merge($item->toArray(), [
        'description' => $item->lyItem->description ?? $item->playAt?->format('Ymd'),
        'cover' => $item->lyItem->ly_meta->cover ?? null,
    ]))->values();

    // 封面：avatar → 归属节目 cover（Manual 专辑由 resolvedTarget 从首个片段反查）
    $cover = $album->cover_url;

    return [
        'data' => [
            'hash_id' => $album->hashId,
            'name' => $album->name,
            'description' => $album->description,
            'cover' => $cover,
            'is_rrule_mode' => $album->is_rrule_mode,
            'count' => $playlist->count(),
            'playlist' => $playlist,
        ],
    ];
});

// ltsnp+cc
Route::get('/program/{lyMeta:code}', function (Request $request, LyMeta $lyMeta) {
    $code = $lyMeta->code;
    $hasManyType = $lyMeta->isLts?"ltsItems":"lyItems";
    $programType = $lyMeta->isLts?"ly_meta":"ly_meta";

    $query = <<<GQL
        {
          data:ly_meta_by_code(code: "$code") {
            id
            name
            code
            cover
            description
            begin_at
            end_at
            remark
            category
            ly_items: $hasManyType (first:$lyMeta->counts_max_list) {
              data {
                id
                alias
                description
                play_at
                path: novaMp3Path
                link: path
                program: $programType {
                  id
                  name
                  code
                }
              }
              paginatorInfo {
                total
                currentPage
                hasMorePages
              }
            }
          }
        }
    GQL;
    $graphQL = app(GraphQL::class);
    $createsContext = app(ContextFactory::class);
    $context = $createsContext->generate($request);
    $result = $graphQL->executeQueryString($query, $context);
    return $result['data']['data']['ly_items'];
});
