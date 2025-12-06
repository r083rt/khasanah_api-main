<?php

/*
|--------------------------------------------------------------------------
| Application Routes Production
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => 'auth'], function ($router) {

    /**
     * Roti Manis Target Plan
     */
    $router->group(['prefix' => 'plan'], function ($router) {
        $router->get('/', 'TargetPlanController@index');
        $router->post('/', 'TargetPlanController@store');

        $router->get('/list/branch', 'TargetPlanController@listBranch');
        $router->get('/list/product', 'TargetPlanController@listProduct');
    });

    /**
     * Brownies
     */
    $router->group(['prefix' => 'brownies-plan'], function ($router) {
        /**
         * Daily Production
         */
        $router->group(['prefix' => 'product'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanProductController@index');
            $router->get('/{day}', 'BrowniesTargetPlanProductController@show');
            $router->post('/', 'BrowniesTargetPlanProductController@store');

            $router->get('/list/branch', 'BrowniesTargetPlanProductController@listBranch');
            $router->get('/list/product', 'BrowniesTargetPlanProductController@listProduct');
        });

        /**
         * Target Sale
         */
        $router->group(['prefix' => 'sale'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanSaleController@index');
            $router->get('/{day}', 'BrowniesTargetPlanSaleController@show');
            $router->post('/', 'BrowniesTargetPlanSaleController@store');

            $router->get('/list/branch', 'BrowniesTargetPlanSaleController@listBranch');
        });

        /**
         * Buffer Production
         */
        $router->group(['prefix' => 'buffer'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanBufferController@index');
            $router->get('/{day}', 'BrowniesTargetPlanBufferController@show');
            $router->post('/', 'BrowniesTargetPlanBufferController@store');

            $router->get('/list/branch', 'BrowniesTargetPlanBufferController@listBranch');
        });

        /**
         * Buffer Target
         */
        $router->group(['prefix' => 'buffer-target'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanBufferTargetController@index');
            $router->post('/', 'BrowniesTargetPlanBufferTargetController@store');

            $router->get('/list/branch', 'BrowniesTargetPlanBufferTargetController@listBranch');
        });

        /**
         * Report
         */
        $router->group(['prefix' => 'report'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanReportController@index');

            $router->get('/list/branch', 'BrowniesTargetPlanReportController@listBranch');
        });

        /**
         * Po Production
         */
        $router->group(['prefix' => 'po'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanProductionController@index');
            $router->post('/', 'BrowniesTargetPlanProductionController@store');
        });

        /**
         * Po Warehouse
         */
        $router->group(['prefix' => 'warehouse'], function ($router) {
            $router->get('/', 'BrowniesTargetPlanWarehouseController@index');
            $router->post('/', 'BrowniesTargetPlanWarehouseController@store');

            $router->get('/list/branch', 'BrowniesTargetPlanWarehouseController@listBranch');
            $router->get('/list/product', 'BrowniesTargetPlanWarehouseController@listProduct');
        });
    });

    /**
     * Cookie
     */
    $router->group(['prefix' => 'cookie'], function ($router) {
        /**
         * Daily Production
         */
        $router->group(['prefix' => 'product'], function ($router) {
            $router->get('/', 'CookieProductController@index');
            $router->get('/{day}', 'CookieProductController@show');
            $router->post('/', 'CookieProductController@store');

            $router->get('/list/branch', 'CookieProductController@listBranch');
            $router->get('/list/product', 'CookieProductController@listProduct');
        });

        /**
         * Target Sale
         */
        $router->group(['prefix' => 'sale'], function ($router) {
            $router->get('/', 'CookieSaleController@index');
            $router->get('/{day}', 'CookieSaleController@show');
            $router->post('/', 'CookieSaleController@store');

            $router->get('/list/branch', 'CookieSaleController@listBranch');
        });

        /**
         * Buffer Production
         */
        $router->group(['prefix' => 'buffer'], function ($router) {
            $router->get('/', 'CookieBufferProductionController@index');
            $router->get('/{day}', 'CookieBufferProductionController@show');
            $router->post('/', 'CookieBufferProductionController@store');

            $router->get('/list/branch', 'CookieBufferProductionController@listBranch');
        });

        /**
         * Buffer Target
         */
        $router->group(['prefix' => 'buffer-target'], function ($router) {
            $router->get('/', 'CookieBufferTargetController@index');
            $router->post('/', 'CookieBufferTargetController@store');

            $router->get('/list/branch', 'CookieBufferTargetController@listBranch');
        });

        /**
         * Po Production
         */
        $router->group(['prefix' => 'po-production'], function ($router) {
            $router->get('/', 'CookieProductionController@index');
            $router->post('/', 'CookieProductionController@store');

            $router->get('/list/branch', 'CookieProductionController@listBranch');
        });
    });

    /**
     * Real Grind & History
     */
    $router->group(['prefix' => 'real-grind'], function ($router) {
        /**
         * Cookie
         */
        $router->group(['prefix' => 'cookie'], function ($router) {
            $router->get('/', 'RealGrindCookieController@index');
            $router->get('/export', 'RealGrindCookieController@export');
            $router->post('/', 'RealGrindCookieController@store');
            $router->get('/{id}', 'RealGrindCookieController@show');
            $router->post('/{id}', 'RealGrindCookieController@update');
            $router->post('/delete/bulk', 'RealGrindCookieController@destroy');

            $router->get('/detail/history', 'RealGrindCookieController@detail');
            $router->get('/list/branch', 'RealGrindCookieController@listBranch');
        });

        /**
         * Brownies
         */
        $router->group(['prefix' => 'brownies'], function ($router) {
            $router->get('/', 'RealGrindBrowniesController@index');
            $router->post('/', 'RealGrindBrowniesController@store');
            $router->get('/{id}', 'RealGrindBrowniesController@show');
            $router->post('/update', 'RealGrindBrowniesController@update');
            $router->post('/delete/bulk', 'RealGrindBrowniesController@destroy');

            $router->get('/list/product', 'RealGrindBrowniesController@listProduct');
        });

        /**
         * Brownies Store
         */
        $router->group(['prefix' => 'brownies-store'], function ($router) {
            $router->get('/', 'RealGrindBrowniesStoreController@index');
            $router->get('/export', 'RealGrindBrowniesStoreController@export');
            $router->post('/', 'RealGrindBrowniesStoreController@store');
            $router->get('/{id}', 'RealGrindBrowniesStoreController@show');
            $router->post('/{id}', 'RealGrindBrowniesStoreController@update');
            $router->post('/delete/bulk', 'RealGrindBrowniesStoreController@destroy');

            $router->get('/detail/history', 'RealGrindBrowniesStoreController@detail');
            $router->get('/list/branch', 'RealGrindBrowniesStoreController@listBranch');
            $router->get('/list/packaging', 'RealGrindBrowniesStoreController@listMasterPackaging');
        });
    });

    /**
     * Brownies
     */
    $router->group(['prefix' => 'brownies-store'], function ($router) {
        /**
         * Po Production
         */
        $router->group(['prefix' => 'po'], function ($router) {
            $router->get('/', 'BrowniesStoreProductionController@index');
            $router->post('/', 'BrowniesStoreProductionController@store');
        });
    });
});
