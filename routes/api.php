<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
            ly_metas{
                id
              name
              code
              avatar:cover
              description
              begin_at
              end_at
            }
          }
        }
    GQL;
    $graphqlEndpoint = config('app.url') . '/graphql';
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $graphqlEndpoint, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'query' => $query
      ]
    ]);

    $json = $response->getBody()->getContents();
    $body = json_decode($json, true);
    return ['data' => $body['data']['data']];
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
    $graphqlEndpoint = config('app.url') . '/graphql';
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $graphqlEndpoint, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'query' => $query
      ]
    ]);

    $json = $response->getBody()->getContents();
    $body = json_decode($json, true);
    return ['data' => $body['data']['data']];
});

Route::get('/today', function (Request $request) {
    return ItemResource::collection(Item::where('play_at', now()->format('Y-m-d 00:00:00'))->inRandomOrder()->get());
});

Route::get('/program/{code}', function (Request $request, $code) {
    // $lyMeta = LyMeta::whereCode($code)->firstOrFail();

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
            ly_items {
              data {
                id
                alias
                description
                play_at
                mp3
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
    $graphqlEndpoint = config('app.url') . '/graphql';
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $graphqlEndpoint, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'query' => $query
      ]
    ]);

    $json = $response->getBody()->getContents();
    $body = json_decode($json, true);
    return ['data' => $body['data']['data']];
});

Route::get('/program/{code}/{date}', function (Request $request, $code, $date) {
    $program = Program::whereAlias($code)->firstOrFail();
    return ItemResource::collection(Item::where('program_id', $program->id)->where('play_at', $date)->get());
});
