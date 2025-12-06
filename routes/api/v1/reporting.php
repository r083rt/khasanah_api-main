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
     * Sale
     */
    $router->group(['prefix' => 'sale'], function ($router) {
        $router->get('/', 'SaleController@index');
        $router->get('/export', 'SaleController@export');

        $router->get('/list/branch', 'SaleController@listBranch');
        $router->get('/list/territory', 'SaleController@listTerritory');
    });

    /**
     * Stock Adjustment
     */
    $router->group(['prefix' => 'incoming-product'], function ($router) {
        $router->get('/', 'ProductStockAdjustmentController@index');
        $router->get('/export', 'ProductStockAdjustmentController@export');

        $router->get('/list/branch', 'ProductStockAdjustmentController@listBranch');
        $router->get('/list/territory', 'ProductStockAdjustmentController@listTerritory');
    });

    /**
     * Expense
     */
    $router->group(['prefix' => 'expense'], function ($router) {
        $router->get('/', 'ExpenseController@index');
        $router->get('/export', 'ExpenseController@export');

        $router->get('/list/branch', 'ExpenseController@listBranch');
        $router->get('/list/territory', 'ExpenseController@listTerritory');
    });

    /**
     * Stock
     */
    $router->group(['prefix' => 'stock'], function ($router) {
        $router->get('/', 'StockController@index');
        $router->get('/export', 'StockController@export');

        $router->get('/list/branch', 'StockController@listBranch');
        $router->get('/list/territory', 'StockController@listTerritory');
    });

    /**
     * Distribution
     */
    $router->group(['prefix' => 'distribution'], function ($router) {
        $router->get('/', 'DistributionController@index');
        $router->get('/export', 'DistributionController@export');

        $router->get('/list/branch', 'DistributionController@listBranch');
        $router->get('/list/territory', 'DistributionController@listTerritory');
    });

    /**
     * Monitoring Selisih Closing
     */
    $router->group(['prefix' => 'monitoring-closing'], function ($router) {
        /**
         * Summary
         */
        $router->group(['prefix' => 'summary'], function ($router) {
            $router->get('/', 'MonitoringClosingController@index');
            $router->get('/export', 'MonitoringClosingController@export');

            $router->get('/list/branch', 'MonitoringClosingController@listBranch');
        });

        /**
         * Difference Stock Closing
         */
        $router->group(['prefix' => 'stock'], function ($router) {
            $router->get('/', 'MonitoringClosingController@differenceClosing');
            $router->get('/export', 'MonitoringClosingController@exportDifferenceStock');
        });

        /**
         * Target & adjustment cookie
         */
        $router->group(['prefix' => 'cookie'], function ($router) {
            $router->get('/', 'MonitoringClosingController@targetCookie');
            $router->get('/export', 'MonitoringClosingController@exportTargetCookie');
        });
    });

    /**
     * History Mutation Stock
     */
    $router->group(['prefix' => 'mutation-stock'], function ($router) {
        $router->get('/', 'MutationStockController@index');
        $router->get('/export', 'MutationStockController@export');

        $router->get('/list/branch', 'MutationStockController@listBranch');
        $router->get('/list/product', 'MutationStockController@listProduct');
    });

    /**
     * History Return & Donation
     */
    $router->group(['prefix' => 'history-return'], function ($router) {
        $router->get('/', 'HistoryReturnController@index');
        $router->get('/export', 'HistoryReturnController@export');

        $router->get('/list/branch', 'HistoryReturnController@listBranch');
        $router->get('/list/product', 'HistoryReturnController@listProduct');
    });

    /**
     * History Order
     */
    $router->group(['prefix' => 'order'], function ($router) {
        $router->get('/', 'HistoryOrderController@index');
        $router->get('/export', 'HistoryOrderController@export');
    });

    /**
     * Ingredient Usage
     */
    $router->group(['prefix' => 'ingredient-usage'], function ($router) {
        $router->get('/', 'IngredientUsageController@index');
        $router->get('/export', 'IngredientUsageController@export');

        $router->get('/list/branch', 'IngredientUsageController@listBranch');
        $router->get('/list/category', 'IngredientUsageController@listCategory');
    });

    /**
     * Po Travel Doc
     */
    $router->group(['prefix' => 'po-travel-doc'], function ($router) {
        $router->get('/', 'PoTravelDocController@index');
        $router->get('/export', 'PoTravelDocController@export');

        $router->get('/list/branch', 'PoTravelDocController@listBranch');
    });

    /**
     * Report Transaction
     */
    $router->group(['prefix' => 'report-transaction'], function ($router) {
        $router->get('/', 'ReportTransactionController@index');
        $router->get('/export', 'ReportTransactionController@export');

        $router->get('/list/branch', 'ReportTransactionController@listBranch');
    });

    /**
     * Po Out Standing
     */
    $router->group(['prefix' => 'po-out-standing'], function ($router) {
        $router->get('/', 'PoOutStandingController@index');
        $router->get('/export', 'PoOutStandingController@export');
    });

    /**
     * Supplier Perform
     */
    $router->group(['prefix' => 'supplier-perform'], function ($router) {
        $router->get('/', 'SupplierPerformController@index');
        $router->get('/export', 'SupplierPerformController@export');
    });

    /**
     * Report Recipe
     */
    $router->group(['prefix' => 'report-recipe'], function ($router) {
        $router->get('/', 'ReportRecipeController@index');
        $router->get('/export', 'ReportRecipeController@export');

        $router->get('/list/ingredient', 'ReportRecipeController@listIngredient');
    });

    /**
     * History Patokan
     */
    $router->group(['prefix' => 'patokan'], function ($router) {
        $router->get('/', 'PatokanController@index');
        $router->get('/export', 'PatokanController@export');
    });

    /**
     * Report Spl
     */
    $router->group(['prefix' => 'spl'], function ($router) {
        $router->get('/', 'SplController@index');
        $router->get('/export', 'SplController@export');
    });

    /**
     * Report Hujt
     */
    $router->group(['prefix' => 'hujt'], function ($router) {
        $router->get('/', 'HujtController@index');
        $router->get('/export', 'HujtController@export');
    });

    /**
     * Report Inventory
     */
    $router->group(['prefix' => 'inventory'], function ($router) {
        $router->get('/', 'InventoryController@index');
        $router->get('/export', 'InventoryController@export');
    });
});
