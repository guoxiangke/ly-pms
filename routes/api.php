<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nuwave\Lighthouse\GraphQL;
// use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Execution\ContextFactory;
use App\Models\LyMeta;
use App\Models\LyItem;
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
    $hasManyType = $lyMeta->isLts?"ltsItems":"ly_items";
    $programType = $lyMeta->isLts?"lts_meta":"ly_meta";

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
            ly_items: $hasManyType {
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
