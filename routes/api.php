<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nuwave\Lighthouse\GraphQL;
// use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Execution\ContextFactory;
use App\Models\LyMeta;
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
    return ['data' => $result['data']['data']];
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
  return $result['data']['ly_items'];
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
