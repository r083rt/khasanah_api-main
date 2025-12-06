<?php

/*
|--------------------------------------------------------------------------
| Application Routes Management
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => 'auth'], function ($router) {

    /**
     * Po Order Product
     */
    $router->group(['prefix' => 'po-order-product'], function ($router) {
        $router->get('/', 'PoOrderProductController@index');
        $router->post('/', 'PoOrderProductController@store');
        $router->get('/{id}', 'PoOrderProductController@show');
        $router->post('/{id}', 'PoOrderProductController@update');
        $router->post('/delete/bulk', 'PoOrderProductController@destroy');

        $router->get('/list/product', 'PoOrderProductController@listProduct');
        $router->get('/list/category', 'PoOrderProductController@listCategory');
        $router->get('/list/branch', 'PoOrderProductController@listBranch');

        /**
         *  Packaging
         */
        $router->get('/packaging/{id}', 'PoOrderProductController@showPackaging');
        $router->post('/packaging/{id}', 'PoOrderProductController@storePackaging');

        /**
         * Status
         */
        $router->post('/status/{id}', 'PoOrderProductController@status');

        $router->get('/list/shipping', 'PoOrderProductController@listShipping');
        $router->get('/list/status', 'PoOrderProductController@listStatus');
        $router->get('/list/all-product', 'PoOrderProductController@listAllProduct');
    });

    /**
     * Po Brownies
     */
    $router->group(['prefix' => 'po-brownies'], function ($router) {
        $router->get('/', 'PoBrowniesController@index');
        $router->get('/{id}', 'PoBrowniesController@show');
        $router->post('/delete/bulk', 'PoBrowniesController@destroy');

        $router->get('/list/product', 'PoBrowniesController@listProduct');
        $router->get('/list/category', 'PoBrowniesController@listCategory');
        $router->get('/list/branch', 'PoBrowniesController@listBranch');

        /**
         *  Packaging
         */
        $router->get('/packaging/{id}', 'PoBrowniesController@showPackaging');
        $router->post('/packaging/{id}', 'PoBrowniesController@storePackaging');

        /**
         * Status
         */
        $router->post('/status/{id}', 'PoBrowniesController@status');

        $router->get('/list/shipping', 'PoBrowniesController@listShipping');
        $router->get('/list/status', 'PoBrowniesController@listStatus');
        $router->get('/list/all-product', 'PoBrowniesController@listAllProduct');
    });

    /**
     * Po Order Ingredient
     */
    $router->group(['prefix' => 'po-order-ingredient'], function ($router) {
        $router->get('/', 'PoOrderIngredientController@index');
        $router->post('/', 'PoOrderIngredientController@store');
        $router->get('/{id}', 'PoOrderIngredientController@show');
        $router->post('/{id}', 'PoOrderIngredientController@update');
        $router->post('/delete/bulk', 'PoOrderIngredientController@destroy');

        $router->get('/list/product', 'PoOrderIngredientController@listBahan');
        $router->get('/list/branch', 'PoOrderIngredientController@listBranch');

        /**
         *  Packaging
         */
        $router->get('/packaging/{id}', 'PoOrderIngredientController@showPackaging');
        $router->post('/packaging/{id}', 'PoOrderIngredientController@storePackaging');

        /**
         * Status
         */
        $router->post('/status/{id}', 'PoOrderIngredientController@status');

        $router->get('/list/shipping', 'PoOrderIngredientController@listShipping');
        $router->get('/list/status', 'PoOrderIngredientController@listStatus');
        $router->get('/list/all-product', 'PoOrderIngredientController@listAllProduct');
    });

    /**
     * General
     */
    $router->group(['prefix' => 'general'], function ($router) {
        /**
         *  Total PO
         */
        $router->get('/total-po', 'GeneralController@poOrderBadge');
    });

    /**
     * Print PO
     */
    $router->group(['prefix' => 'po-sj'], function ($router) {
        $router->get('/', 'PrintController@index');
        $router->post('/{id}', 'PrintController@store');

        $router->get('/list/branch', 'PrintController@listBranch');
    });

    /**
     * Receive PO
     */
    $router->group(['prefix' => 'po-receive'], function ($router) {
        $router->get('/', 'PoReceiveController@index');
        $router->get('/barcode/{barcode}', 'PoReceiveController@checkBarcode');
        $router->post('/', 'PoReceiveController@store');

        $router->get('/list/product', 'PoReceiveController@listProduct');
        $router->get('/list/ingredient', 'PoReceiveController@listBahan');
    });

    /**
     * Adjustmnet PO Ingredient
     */
    $router->group(['prefix' => 'po-adjustment-ingredient'], function ($router) {
        $router->get('/', 'PoAdjustmentOrderIngredientController@index');
        $router->get('/{id}', 'PoAdjustmentOrderIngredientController@show');
        $router->post('/{id}', 'PoAdjustmentOrderIngredientController@approval');

        $router->get('/list/ingredient', 'PoAdjustmentOrderIngredientController@listBahan');
    });

    /**
     * Adjustmnet PO Product
     */
    $router->group(['prefix' => 'po-adjustment-product'], function ($router) {
        $router->get('/', 'PoAdjustmentOrderProductController@index');
        $router->get('/{id}', 'PoAdjustmentOrderProductController@show');
        $router->post('/{id}', 'PoAdjustmentOrderProductController@approval');

        $router->get('/list/product', 'PoAdjustmentOrderProductController@listProduct');
    });

    /**
     * Adjustmnet PO Brownies
     */
    $router->group(['prefix' => 'po-adjustment-brownies'], function ($router) {
        $router->get('/', 'PoAdjustmentBrowniesController@index');
        $router->get('/{id}', 'PoAdjustmentBrowniesController@show');
        $router->post('/{id}', 'PoAdjustmentBrowniesController@approval');

        $router->get('/list/product', 'PoAdjustmentBrowniesController@listProduct');
    });

    /**
     * Adjustmnet PO Manual
     */
    $router->group(['prefix' => 'po-adjustment-manual'], function ($router) {
        $router->get('/', 'PoAdjustmentManualController@index');
        $router->get('/{id}', 'PoAdjustmentManualController@show');
        $router->post('/{id}', 'PoAdjustmentManualController@approval');

        $router->get('/list/ingredient', 'PoAdjustmentManualController@listBahan');
        $router->get('/list/product', 'PoAdjustmentManualController@listProduct');
    });

    /**
     * Po Manual
     */
    $router->group(['prefix' => 'po-manual'], function ($router) {
        $router->get('/', 'PoManualController@index');
        $router->get('/export', 'PoManualController@export');
        $router->post('/', 'PoManualController@store');
        $router->get('/{id}', 'PoManualController@show');
        $router->post('/{id}', 'PoManualController@update');
        $router->post('/delete/bulk', 'PoManualController@destroy');
        $router->post('/import/file', 'PoManualController@import');

        $router->get('/list/ingredient', 'PoManualController@listBahan');
        $router->get('/list/product', 'PoManualController@listProduct');
        $router->get('/list/branch', 'PoManualController@listBranch');

        /**
         * Approval PO
         */
        $router->post('/approval/{id}', 'PoManualController@centralApproval');

        /**
         *  Packaging
         */
        $router->get('/packaging/{id}', 'PoManualController@showPackaging');
        $router->post('/packaging/{id}', 'PoManualController@storePackaging');

        /**
         * Status
         */
        $router->post('/status/{id}', 'PoManualController@status');

        $router->get('/list/shipping', 'PoManualController@listShipping');
        $router->get('/list/status', 'PoManualController@listStatus');
        $router->get('/list/all-product', 'PoManualController@listAllProduct');
    });
});
