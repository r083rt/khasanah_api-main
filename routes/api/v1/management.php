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
    $router->post('notification/token', 'UserNotificationTokenController@store');

    /**
     * User
     */
    $router->group(['prefix' => 'user'], function ($router) {
        $router->get('/', 'UserController@index');
        $router->post('/', 'UserController@store');
        $router->get('/{id}', 'UserController@show');
        $router->post('/{id}', 'UserController@update');
        $router->post('/delete/bulk', 'UserController@destroy');

        $router->get('/list/role', 'UserController@listRole');
        $router->get('/list/branch', 'UserController@listBranch');
    });

    /**
     * Branch
     */
    $router->group(['prefix' => 'branch'], function ($router) {
        $router->get('/', 'BranchController@index');
        $router->post('/', 'BranchController@store');
        $router->get('/{id}', 'BranchController@show');
        $router->post('/{id}', 'BranchController@update');
        $router->post('/delete/bulk', 'BranchController@destroy');

        $router->get('/list/area', 'BranchController@listArea');
        $router->get('/list/territory', 'BranchController@listTerritory');

        $router->group(['prefix' => 'child'], function ($router) {
            /**
             * Branch Setting
             */
            $router->group(['prefix' => 'setting'], function ($router) {
                $router->get('/', 'BranchSettingController@index');
                $router->post('/', 'BranchSettingController@store');
                $router->get('/{id}', 'BranchSettingController@show');
                $router->post('/{id}', 'BranchSettingController@update');
                $router->post('/delete/bulk', 'BranchSettingController@destroy');

                $router->get('/list/branch', 'BranchSettingController@listBranch');
            });

            /**
             * Territory
             */
            $router->group(['prefix' => 'territory'], function ($router) {
                $router->get('/', 'TerritoryController@index');
                $router->post('/', 'TerritoryController@store');
                $router->get('/{id}', 'TerritoryController@show');
                $router->post('/{id}', 'TerritoryController@update');
                $router->post('/delete/bulk', 'TerritoryController@destroy');
            });

            /**
             * Discount
             */
            $router->group(['prefix' => 'discount'], function ($router) {
                $router->get('/', 'BranchDiscountController@index');
                $router->post('/', 'BranchDiscountController@store');
                $router->get('/{id}', 'BranchDiscountController@show');
                $router->post('/update', 'BranchDiscountController@update');
                $router->post('/delete/bulk', 'BranchDiscountController@destroy');

                $router->get('/list/branch', 'BranchDiscountController@listBranch');
                $router->get('/list/category', 'BranchDiscountController@listCategory');
                $router->get('/list/product', 'BranchDiscountController@listProduct');
            });

            /**
             * Area
             */
            $router->group(['prefix' => 'area'], function ($router) {
                $router->get('/', 'AreaController@index');
                $router->post('/', 'AreaController@store');
                $router->get('/{id}', 'AreaController@show');
                $router->post('/{id}', 'AreaController@update');
                $router->post('/delete/bulk', 'AreaController@destroy');

                $router->get('/list/territory', 'AreaController@listTerritory');
            });
        });
    });

    /**
     * Role
     */
    $router->group(['prefix' => 'role'], function ($router) {
        $router->get('/', 'RoleController@index');
        $router->get('/list/menu', 'RoleController@listMenu');
        $router->post('/', 'RoleController@store');
        $router->get('/{id}', 'RoleController@show');
        $router->post('/{id}', 'RoleController@update');
        $router->post('/delete/bulk', 'RoleController@destroy');
    });

    /**
     * Menu
     */
    $router->group(['prefix' => 'menu'], function ($router) {
        $router->get('/', 'MenuController@index');
        $router->get('/all/access', 'MenuController@allAccess');
        $router->post('/', 'MenuController@store');
        $router->get('/{id}', 'MenuController@show');
        $router->post('/{id}', 'MenuController@update');
        $router->post('/delete/bulk', 'MenuController@destroy');
    });

    /**
     * Customer
     */
    $router->group(['prefix' => 'customer'], function ($router) {
        $router->get('/', 'CustomerController@index');
        $router->post('/', 'CustomerController@store');
        $router->get('/{id}', 'CustomerController@show');
        $router->post('/{id}', 'CustomerController@update');
        $router->post('/delete/bulk', 'CustomerController@destroy');

        $router->get('/list/category', 'CustomerController@listCategory');
        $router->get('/list/product', 'CustomerController@listProduct');
    });

    /**
     * User Session
     */
    $router->group(['prefix' => 'session'], function ($router) {
        $router->get('/', 'UserSessionController@index');
        $router->post('/delete/bulk', 'UserSessionController@destroy');
    });

    /**
     * Shipping
     */
    $router->group(['prefix' => 'shipping'], function ($router) {
        $router->get('/', 'ShippingController@index');
        $router->post('/', 'ShippingController@store');
        $router->get('/{id}', 'ShippingController@show');
        $router->post('/{id}', 'ShippingController@update');
        $router->post('/delete/bulk', 'ShippingController@destroy');

        $router->get('/list/branch', 'ShippingController@listBranch');
    });

    /**
     * Supplier
     */
    $router->group(['prefix' => 'supplier'], function ($router) {
        $router->get('/', 'SupplierController@index');
        $router->post('/', 'SupplierController@store');
        $router->post('/{id}', 'SupplierController@update');
        $router->post('/delete/bulk', 'SupplierController@destroy');

        $router->get('/list/branch', 'SupplierController@listBranch');
        $router->get('/list/product-ingredient', 'SupplierController@listProductIngredient');
        $router->get('/list/product', 'SupplierController@listProduct');
    });

    /**
     * Division
     */
    $router->group(['prefix' => 'division'], function ($router) {
        $router->get('/', 'DivisionController@index');
        $router->post('/', 'DivisionController@store');
        $router->post('/{id}', 'DivisionController@update');
        $router->get('/{id}', 'DivisionController@show');
        $router->post('/delete/bulk', 'DivisionController@destroy');
    });

    /**
     * Sub Division
     */
    $router->group(['prefix' => 'sub-division'], function ($router) {
        $router->get('/', 'SubDivisionController@index');
        $router->post('/', 'SubDivisionController@store');
        $router->post('/{id}', 'SubDivisionController@update');
        $router->get('/{id}', 'SubDivisionController@show');
        $router->post('/delete/bulk', 'SubDivisionController@destroy');

        $router->get('/list/division', 'SubDivisionController@listDivision');
    });

    /**
     * User Notification
     */
    $router->group(['prefix' => 'notification'], function ($router) {
        $router->get('/', 'UserNotificationController@index');
        $router->post('/{id}', 'UserNotificationController@update');
        $router->post('/', 'UserNotificationController@markAsRead');
    });
});
