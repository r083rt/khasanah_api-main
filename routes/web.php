<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer', 'middleware' => 'log'], function () use ($router) {
    $router->get('logs/{token}/', 'LogViewerController@index');
});

$router->get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});

$router->post('/test-gform', function (Request $request) {
    Log::info('GFORM: ' . json_encode($request->all()));
});

// $router->group(['prefix' => 'pdf'], function (\Laravel\Lumen\Routing\Router $router) {
//     $router->get('/po-supplier-detail', function () {
//         // return view('pdf.po-supplier-detail');
//         return response()->stream(function () {
//             echo generate_pdf('pdf.po-supplier-detail', [], 'test.pdf', false);
//         }, 200, ['Content-Type' => 'application/pdf']);
//     });
// });

$router->get('/storages/po_supplier/{name}', function ($name) {
    return response()->download(storage_path('app/po_supplier/' . $name), $name, [], 'inline');
});
