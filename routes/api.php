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
          data:tags{
            id
            name
            type
            order_column
            ly_metas{
                id
              name
              code
              avatar
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
Route::get('/programs', function (Request $request) {
    $query = <<<GQL
        {
          data:ly_metas{
            id
            name
            avatar
            category
            alias:code
            link:api_url
            begin_at
            end_at:stop_at
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