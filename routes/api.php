<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nuwave\Lighthouse\GraphQL;
// use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Execution\ContextFactory;
use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\Content;
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

Route::get('/resource/find/video/poster', function (Request $request) {
    return [
        'code' => 200,
        'msg' => 'ok',
        'data' => [
            [
                'id' => 0,
                'typed' => 5,//海报的类型:0=视频,1=音频,2=内部链接,3=外部链接,4=文本,5=合集
                'cid' => 37,//合集id
                'title' => '真道分解',
                'icon' => "https://r2.pdbo.uk/3a1b8a50-1114-6ea8-6e1c-773b662c9a1e.jpeg",
                'len' => 365,
                'sorts' => 0
            ],
            [
                'id' => 1,
                'typed' => 5,
                'cid' => 49,
                'title' => "良友圣经学院",
                'icon' => "https://r2.pdbo.uk/3a1b99fd-0719-d54e-0f7c-b0b2bc576e30.jpg",
                'len' => 365,
                'sorts' => 1
            ],
            [
                'id' => 0,
                'typed' => 5,
                'cid' => 37,
                'title' => '真道分解',
                'icon' => "https://r2.pdbo.uk/3a1b8a50-1114-6ea8-6e1c-773b662c9a1e.jpeg",
                'len' => 365,
                'sorts' => 2
            ],
            [
                'id' => 1,
                'typed' => 5,
                'cid' => 49,
                'title' => "良友圣经学院",
                'icon' => "https://r2.pdbo.uk/3a1b99fd-0719-d54e-0f7c-b0b2bc576e30.jpg",
                'len' => 365,
                'sorts' => 3
            ],
        ]
    ];
});

Route::get('/resource/find/video/recom', function (Request $request) {
    return [
        'code' => 200,
        'msg' => 'ok',
        'data' => [
            'version' => time(),
            'data' => [
                [
                    'id' => 39,
                    'title' => "穿越圣经",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ttb.jpg",
                    'iconDark' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ttb.jpg",
                    'rows' => fake()->numberBetween(1, 5),
                    'width' => fake()->numberBetween(100, 500),
                    'sorts' => 1,
                ],
                [
                    'id' => 41,
                    'title' => "与神同行",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/it.jpg",
                    'iconDark' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/it.jpg",
                    'rows' => fake()->numberBetween(1, 5),
                    'width' => fake()->numberBetween(100, 500),
                    'sorts' => 2,
                ],
                [
                    'id' => 28,
                    'title' => "旷野吗哪",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                    'iconDark' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                    'rows' => fake()->numberBetween(1, 5),
                    'width' => fake()->numberBetween(100, 500),
                    'sorts' => 3,
                ],
            ]
        ]
    ];
});

Route::get('/resource/find/video/list', function (Request $request) {
    return [
        'code' => 200,
        'msg' => 'ok',
        'data' => [
            [
                'id' => 29,
                'title' => "献上今天",
                'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/dy.jpg",
                'total' => 365,
                'tag' => 2,
                'nickname' => "献上今天",
                'sorts' => 0,
                'version' => 0,
                'infos' => "良友电台",
                'summary' => "为神赐的今天加油",
            ],
            [
                'id' => 28,
                'title' => "旷野吗哪",
                'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                'total' => 365,
                'tag' => 2,
                'nickname' => "旷野吗哪",
                'sorts' => 1,
                'version' => 0,
                'infos' => "良友电台",
                'summary' => "提供每日灵粮",
            ],
            [
                'id' => 29,
                'title' => "与神同行",
                'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/it.jpg",
                'total' => 365,
                'tag' => 2,
                'nickname' => "与神同行",
                'sorts' => 0,
                'version' => 0,
                'infos' => "良友电台",
                'summary' => "查尔斯史达尼牧师讲章",
            ],
        ]
    ];
});

Route::get('/resource/find/video/list/details', function (Request $request) {
    return [
        'code' => 200,
        'msg' => 'ok',
        'data' => [
            'list' => [
                'id' => 28,
                'title' => "旷野吗哪",
                'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                'infos' => "提供每日灵粮",
                'summary' => "提供每日灵粮",
                'total' => 365,
                'tag' => 2,
                'sorts' => 1,
                'lt' => 'lt',
                'mt' => 'mt',
                'st' => 'st',
                'ult' => 'ult',
                'umt' => 'umt',
                'ust' => 'ust',
                'nickname' => "旷野吗哪nickname",
                'cover' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                'version' => 0
            ],
            'items' => [
                    // 'fms' => json_encode([['fs' => 10, 'ft' => fake()->sentence()], ['fs' => 30, 'ft' => fake()->sentence()]]),
                    // 'srt' => fake()->url(),
                    // 'infosUrl' => fake()->url()
                [
                    'id' => 1,
                    'title' => "旷野吗哪251211",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                    'len' => fake()->time(),
                    'lenss' => fake()->numberBetween(60, 3600),
                    'links' => 1,//是否为外部内容: 0=不是,1=是(必须)
                    'url' => "https://x.lydt.work/storage/ly/audio/2025/mw/mw251212.mp3",
                    'lw' => 'lw',
                    'mw' => 'mw',
                    'sw' => 'sw',
                    'ulw' => 'ulw',
                    'umw' => 'umw',
                    'usw' => 'usw',
                    'vindex' => 0,
                    'vpath' => 0,
                    'sorts' => 0,
                    'infos' => "信心与忍耐（彼得后书1:3-11）",
                ], [
                    'id' => 2,
                    'title' => "旷野吗哪251210",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                    'len' => fake()->time(),
                    'lenss' => fake()->numberBetween(60, 3600),
                    'links' => 1,//是否为外部内容: 0=不是,1=是(必须)
                    'url' => "https://x.lydt.work/storage/ly/audio/2025/mw/mw251210.mp3",
                    'lw' => 'lw',
                    'mw' => 'mw',
                    'sw' => 'sw',
                    'ulw' => 'ulw',
                    'umw' => 'umw',
                    'usw' => 'usw',
                    'vindex' => 0,
                    'vpath' => 0,
                    'sorts' => 0,
                    'infos' => "信心之旅（希伯来书10:32-11:2、39-40）",
                ],
                 [
                    'id' => 3,
                    'title' => "旷野吗哪251209",
                    'icon' => "https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/mw.jpg",
                    'len' => fake()->time(),
                    'lenss' => fake()->numberBetween(60, 3600),
                    'links' => 1,//是否为外部内容: 0=不是,1=是(必须)
                    'url' => "https://x.lydt.work/storage/ly/audio/2025/mw/mw251209.mp3",
                    'lw' => 'lw',
                    'mw' => 'mw',
                    'sw' => 'sw',
                    'ulw' => 'ulw',
                    'umw' => 'umw',
                    'usw' => 'usw',
                    'vindex' => 0,
                    'vpath' => 0,
                    'sorts' => 0,
                    'infos' => "保罗──信主得永生之人的榜样（提摩太前书1:12-20）",
                ],
            ]
        ]
    ];
});
