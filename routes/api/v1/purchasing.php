<?php

/*
|--------------------------------------------------------------------------
| Application Routes Purchasing
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'po-receive'], function ($router) {
    $router->get('/all', 'PoReceiveController@all');
});

$router->group(['middleware' => 'auth'], function ($router) {
    /**
     * Forecast
     */
    $router->group(['prefix' => 'forecast'], function ($router) {
        $router->get('/', 'ForecastController@index');
        $router->get('/{month}', 'ForecastController@show');
        $router->post('/import', 'ForecastController@import');
        $router->get('/import/preview', 'ForecastController@importPreview');
        $router->post('/import/submit', 'ForecastController@importSubmit');

        $router->get('/list/branch', 'ForecastController@listBranch');

        $router->get('/additional/{month}', 'ForecastController@showAdditional');
    });

    /**
     * Forecast Conversion
     */
    $router->group(['prefix' => 'forecast-conversion'], function ($router) {
        $router->get('/', 'ForecastConversionController@index');
        $router->get('/additional/{month}', 'ForecastConversionController@additonal_show');
        $router->get('/{month}', 'ForecastConversionController@show');
        $router->post('/', 'ForecastConversionController@update');
        $router->post('/regenerate', 'ForecastConversionController@regenerate');

        $router->get('/list/branch', 'ForecastConversionController@listBranch');
        $router->get('/{month}/export', 'ForecastConversionController@export');
    });

    /**
     * Approval Forecast Conversion
     */
    $router->group(['prefix' => 'approval-forecast-conversion'], function ($router) {
        $router->get('/', 'ForecastConversionApprovalController@index');
        $router->get('/{id}', 'ForecastConversionApprovalController@show');
        $router->post('/{id}', 'ForecastConversionApprovalController@update');

        $router->get('/history/approved/', 'ForecastConversionApprovalController@history');
    });

    /**
     * Trend
     */
    $router->group(['prefix' => 'trend'], function ($router) {
        $router->get('/', 'TrendController@index');
        $router->get('/{month}', 'TrendController@show');
        $router->post('/{month}', 'TrendController@update');
    });

    /**
     * Buffer
     */
    $router->group(['prefix' => 'buffer'], function ($router) {
        $router->get('/', 'ForecastBufferController@index');
        $router->get('/{id}', 'ForecastBufferController@show');
        $router->post('/', 'ForecastBufferController@store');
        $router->post('/update/default', 'ForecastBufferController@default');
    });

    /**
     * Purchasing Supplier
     */
    $router->group(['prefix' => 'supplier'], function ($router) {
        $router->get('/', 'SupplierController@index');
        $router->get('/all', 'SupplierController@getAll');
        $router->post('/', 'SupplierController@store');
        $router->get('/{id}', 'SupplierController@show');
        $router->post('/{id}', 'SupplierController@update');
        $router->post('/delete/bulk', 'SupplierController@destroy');
    });

    /**
     * Setting Po
     */
    $router->group(['prefix' => 'setting-po'], function ($router) {
        $router->get('/', 'SettingPoController@index');
        $router->get('/generateNumber', 'SettingPoController@generateNumber');
        $router->get('/export/{id}', 'SettingPoController@export');
        $router->get('/qty_export/{id}', 'SettingPoController@qtyexport');
        $router->get('/{id}', 'SettingPoController@show');
        $router->post('/{id}', 'SettingPoController@store');

        $router->post('/edit_qty/{id}', 'SettingPoController@store_qty');

        $router->get('/list/supplier', 'SettingPoController@listSupplier');
        $router->get('/list/branches', 'SettingPoController@listBranch');

        $router->get('/list/brand_products/{id}', 'SettingPoController@brandProduct');
    });

    /**
     * Po Supplier
     */
    $router->group(['prefix' => 'po-supplier'], function ($router) {
        $router->get('/', 'PoSupplierController@index');
        $router->get('/partial', 'PoSupplierController@partial');
        $router->get('/{id}', 'PoSupplierController@show');
        $router->post('/create', 'PoSupplierController@store');
        $router->post('/{id}', 'PoSupplierController@sendEmail');

        $router->get('/list/supplier', 'PoSupplierController@listSupplier');
    });

    /**
     * Po Receive
     */
    $router->group(['prefix' => 'po-receive'], function ($router) {
        $router->get('/', 'PoReceiveController@index');
        $router->get('/{id}', 'PoReceiveController@show');
        $router->get('/{id}/barcode/{barcode}', 'PoReceiveController@scanBarcode');
        $router->post('/{id}', 'PoReceiveController@store');

        $router->get('/list/supplier', 'PoReceiveController@listSupplier');
    });

    /**
     * Stock Opname
     */
    $router->group(['prefix' => 'stock-opname'], function ($router) {
        $router->get('/', 'StockOpnameController@index');
        $router->get('/export', 'StockOpnameController@export');
        // $router->post('/', 'StockOpnameController@store');
        $router->post('/import', 'StockOpnameController@import');
        $router->get('/import/preview', 'StockOpnameController@importPreview');
        $router->post('/import/submit', 'StockOpnameController@importSubmit');
        $router->post('/{id}', 'StockOpnameController@update');
        $router->get('/{id}', 'StockOpnameController@show');
        $router->post('/delete/bulk', 'StockOpnameController@destroy');

        $router->get('/list/branch', 'StockOpnameController@listBranch');
    });

    /**
     * Po Return
     */
    $router->group(['prefix' => 'po-return'], function ($router) {
        $router->get('/', 'PoReturnController@index');
        $router->get('/{id}', 'PoReturnController@show');
        $router->get('/{id}/barcode/{barcode}', 'PoReturnController@scanBarcode');
        $router->post('/', 'PoReturnController@store');
        $router->post('/{id}', 'PoReturnController@update');
        $router->post('/delete/bulk', 'PoReturnController@destroy');

        $router->get('/list/supplier', 'PoReturnController@listSupplier');
        $router->get('/list/products', 'PoReturnController@listProduct');
        $router->get('/list/po_suppliers', 'PoReturnController@listPoSuppliers');
        $router->get('/list/po_supplier_products', 'PoReturnController@listPoSupplierProducts');
    });
});


