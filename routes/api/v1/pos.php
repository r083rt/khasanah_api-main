<?php

/*
|--------------------------------------------------------------------------
| Application Routes Pos
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => 'auth'], function ($router) {
    /**
     * Cashier
     */
    $router->group(['prefix' => 'cashier'], function ($router) {
        $router->post('/', 'CashierController@store');
        $router->post('/history', 'CashierController@history');
        $router->post('/history/delete', 'CashierController@destroy');
        $router->post('/history/update/{id}', 'CashierController@update');
        $router->post('/create-customer', 'CashierController@storeCustomer');

        $router->post('/update/cart', 'CashierController@updateCart');
        $router->get('/list/product', 'CashierController@listProduct');
        $router->get('/list/customer', 'CashierController@listCustomer');
        $router->get('/list/customer-product', 'CashierController@listCustomerAndProduct');
        $router->get('/list/payment', 'CashierController@listPayment');
        $router->get('/list/product-category', 'CashierController@listCategory');
        $router->get('/checking', 'CashierController@checkingClosing');
    });

    /**
     * Orders
     */
    $router->group(['prefix' => 'order'], function ($router) {
        $router->post('/', 'OrderController@store');
        $router->post('/history', 'OrderController@history');
        $router->post('/history/delete', 'OrderController@destroy');
        $router->post('/history/update', 'OrderController@update');
        $router->post('/create-customer', 'OrderController@storeCustomer');

        $router->get('/list/product', 'OrderController@listProduct');
        $router->get('/list/product-category', 'OrderController@listProductCategory');
        $router->get('/list/customer', 'OrderController@listCustomer');
        $router->get('/list/payment', 'OrderController@listPayment');
        $router->get('/list/product-category', 'OrderController@listCategory');
        $router->get('/checking', 'OrderController@checkingClosing');

        /**
        * History Orders
        */
        $router->group(['prefix' => 'history'], function ($router) {
            $router->get('/', 'HistoryOrderController@index');
            $router->get('/{id}', 'HistoryOrderController@show');

            $router->get('/list/product-category', 'HistoryOrderController@listProductCategory');
            $router->get('/list/branch', 'HistoryOrderController@listBranch');
        });

        /**
        * Summary Orders
        */
        $router->group(['prefix' => 'summary'], function ($router) {
            $router->get('/', 'SummaryOrderController@index');
            $router->get('/{id}', 'SummaryOrderController@show');
            $router->post('/store/{id}', 'SummaryOrderController@store');
            $router->post('/update/{id}', 'SummaryOrderController@update');
            $router->delete('/payment/{id}', 'SummaryOrderController@deletePayment');
            $router->post('/delete', 'SummaryOrderController@destroy');
            $router->delete('/delete/product/{id}', 'SummaryOrderController@deleteProduct');
            $router->get('/refund/dp/{id}', 'SummaryOrderController@refundDp');

            $router->get('/list/branch', 'SummaryOrderController@listBranch');
            $router->get('/list/product', 'SummaryOrderController@listProduct');
        });

        /**
        * Detail Product Orders
        */
        $router->group(['prefix' => 'product'], function ($router) {
            $router->get('/', 'DetailProductOrderController@index');

            $router->get('/list/product-category', 'DetailProductOrderController@listProductCategory');
        });

        /**
        * Ingredient Orders
        */
        $router->group(['prefix' => 'ingredient'], function ($router) {
            $router->get('/', 'IngredientOrderController@index');

            $router->get('/list/branch', 'IngredientOrderController@listBranch');
        });

        /**
        * Customer Orders
        */
        $router->group(['prefix' => 'customer'], function ($router) {
            $router->get('/', 'CustomerOrderController@index');
            $router->get('/{id}', 'CustomerOrderController@show');

            $router->get('/list/customer', 'CustomerOrderController@listCustomer');
        });
    });

    /**
     * Expenses
     */
    $router->group(['prefix' => 'expense'], function ($router) {
        $router->get('/', 'ExpenseController@index');
        $router->post('/', 'ExpenseController@store');
        $router->get('/{id}', 'ExpenseController@show');
        $router->post('/{id}', 'ExpenseController@update');
        $router->post('/delete/bulk', 'ExpenseController@destroy');

        $router->get('/list/ingredient', 'ExpenseController@listIngredient');
        $router->get('/list/master', 'ExpenseController@listMaster');
    });

    /**
     * Closing
     */
    $router->group(['prefix' => 'closing'], function ($router) {
        $router->get('/', 'ClosingController@index');
        $router->post('/', 'ClosingController@store');

        $router->post('/money', 'ClosingController@storeMoney');
    });

    /**
     * Closing Detail / Bendahara
     */
    $router->group(['prefix' => 'closing-detail'], function ($router) {
        $router->get('/', 'ClosingDetailController@index');
        $router->post('/{id}', 'ClosingDetailController@update');
        $router->post('/check/admin', 'ClosingDetailController@checkAdmin');
        $router->get('/export', 'ClosingDetailController@export');

        $router->get('/list/branch', 'ClosingDetailController@listBranch');
    });

    /**
     * Master Expense
     */
    $router->group(['prefix' => 'master-expense'], function ($router) {
        $router->get('/', 'MasterExpenseController@index');
        $router->post('/', 'MasterExpenseController@store');
        $router->get('/{id}', 'MasterExpenseController@show');
        $router->post('/{id}', 'MasterExpenseController@update');
        $router->post('/delete/bulk', 'MasterExpenseController@destroy');
    });
});
