<?php

/*
|--------------------------------------------------------------------------
| Application Routes Auth
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->post('login', 'AuthController@login');

$router->group(['middleware' => 'auth'], function ($router) {
    $router->post('logout', 'AuthController@logout');
    $router->post('refresh', 'AuthController@refresh');
    $router->get('me', 'AuthController@me');

    $router->post('update-profile', 'ProfileController@update');
    $router->post('reset-password', 'ProfileController@reset');
    $router->post('delete', 'ProfileController@destroy');
});
