<?php

/*
|--------------------------------------------------------------------------
| Application Routes Invetory
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'products'], function ($router) {
    // recipe
    $router->get('/recipe/all', 'ProductRecipeController@all');
    $router->get('/recipe/{id}', 'ProductRecipeController@show');
    $router->post('/recipe/add', 'ProductRecipeController@store');
    $router->post('/recipe/delete/bulk', 'ProductRecipeController@destroy');
    $router->post('/recipe/update', 'ProductRecipeController@update');

    // ingredient
    $router->get('/ingredient/all', 'ProductIngredientController@all');
    $router->post('/ingredient/add', 'ProductIngredientController@store');
    $router->get('/ingredient/{id}', 'ProductIngredientController@show');
    $router->post('/ingredient/{id}', 'ProductIngredientController@update');
    $router->post('/delete/bulk', 'ProductIngredientController@destroy');

    // packaging
    $router->get('/packaging/all', 'PackagingController@all');
    $router->post('/packaging/add', 'PackagingController@store');
    $router->get('/packaging/{id}', 'PackagingController@show');
    $router->post('/packaging/{id}', 'PackagingController@update');
    $router->post('/packaging/delete/bulk', 'PackagingController@destroy');

    // product
    $router->get('/all', 'ProductController@all');
    $router->post('/add', 'ProductController@store');
    $router->post('/update/{id}', 'ProductController@update');
    $router->post('/api_delete/bulk', 'ProductController@destroy');
    $router->get('/api/{id}', 'ProductController@show');
});

$router->group(['middleware' => 'auth'], function ($router) {
    /**
     * Product
     */
    $router->group(['prefix' => 'product'], function ($router) {
        $router->get('/', 'ProductController@index');
        $router->post('/', 'ProductController@store');
        $router->get('/{id}', 'ProductController@show');
        
        $router->post('/{id}', 'ProductController@update');
        $router->post('/import/excel', 'ProductController@importExcel');
        $router->post('/delete/bulk', 'ProductController@destroy');

        $router->get('/list/category', 'ProductController@listCategory');
        $router->get('/list/unit', 'ProductController@listUnit');
        $router->get('/list/branch', 'ProductController@listBranch');
        $router->get('/product/code', 'ProductController@productCode');

        $router->group(['prefix' => 'child'], function ($router) {
            /**
             * Product Category
             */
            $router->group(['prefix' => 'category'], function ($router) {
                $router->get('/', 'ProductCategoryController@index');
                $router->post('/', 'ProductCategoryController@store');
                $router->get('/{id}', 'ProductCategoryController@show');
                $router->post('/{id}', 'ProductCategoryController@update');
                $router->post('/delete/bulk', 'ProductCategoryController@destroy');
            });

            /**
             * Product Unit
             */
            $router->group(['prefix' => 'unit'], function ($router) {
                $router->get('/', 'ProductUnitController@index');
                $router->post('/', 'ProductUnitController@store');
                $router->get('/{id}', 'ProductUnitController@show');
                $router->post('/{id}', 'ProductUnitController@update');
                $router->post('/delete/bulk', 'ProductUnitController@destroy');
            });

            /**
             * Product Ingredient
             */
            $router->group(['prefix' => 'ingredient'], function ($router) {
                $router->get('/', 'ProductIngredientController@index');
                $router->get('/all', 'ProductIngredientController@getAll');
                $router->post('/update', 'ProductIngredientController@updatePatokan');
                $router->post('/', 'ProductIngredientController@store');
                $router->get('/{id}', 'ProductIngredientController@show');
                $router->post('/{id}', 'ProductIngredientController@update');
                $router->get('/search/{id}', 'ProductIngredientController@searchByBarcode');
                $router->post('/delete/bulk', 'ProductIngredientController@destroy');

                $router->get('/list/unit', 'ProductIngredientController@listUnit');
                $router->get('/list/brand', 'ProductIngredientController@listBrand');
                $router->get('/list/supplier', 'ProductIngredientController@listSupplier');
                $router->post('/import/excel', 'ProductIngredientController@import');
            });

            /**
             * Product Recipe
             */
            $router->group(['prefix' => 'recipe'], function ($router) {
                $router->get('/', 'ProductRecipeController@index');
                $router->get('{id}', 'ProductRecipeController@show');
                $router->post('/', 'ProductRecipeController@store');
                $router->post('/delete/bulk', 'ProductRecipeController@destroy');
                $router->post('update', 'ProductRecipeController@update');


                $router->get('/list/ingredient', 'ProductRecipeController@listIngredient');
                $router->get('/list/unit', 'ProductRecipeController@listUnit');
                $router->get('/list/division', 'ProductRecipeController@listDivision');
                $router->get('/list/packaging', 'ProductRecipeController@listPackaing');
            });

            /**
             * Product Recipe Unit
             */
            $router->group(['prefix' => 'recipe-unit'], function ($router) {
                $router->get('/', 'ProductRecipeUnitController@index');
                $router->get('/child', 'ProductRecipeUnitController@indexChild');
                $router->get('{id}', 'ProductRecipeUnitController@show');
                $router->post('/', 'ProductRecipeUnitController@store');
                $router->post('/{id}', 'ProductRecipeUnitController@update');
                $router->post('/delete/bulk', 'ProductRecipeUnitController@destroy');

                $router->get('/list/parent', 'ProductRecipeUnitController@listParent');
            });
        });
    });

    /**
     * Product Incoming
     */
    $router->group(['prefix' => 'incoming-product'], function ($router) {
        $router->get('/', 'ProductIncomingController@index');
        $router->post('/', 'ProductIncomingController@store');
        $router->get('/{id}', 'ProductIncomingController@show');
        $router->post('/{id}', 'ProductIncomingController@update');
        $router->post('/delete/bulk', 'ProductIncomingController@destroy');

        $router->get('/list/product', 'ProductIncomingController@listProduct');
    });

    /**
     * Product Stock
     */
    $router->group(['prefix' => 'stock-product'], function ($router) {
        $router->get('/', 'ProductStockController@index');
    });

    /**
     * Product Ingredient Stock
     */
    $router->group(['prefix' => 'stock-ingredient'], function ($router) {
        $router->get('/', 'ProductIngredientStockController@index');
    });

    /**
     * Brand
     */
    $router->group(['prefix' => 'brand'], function ($router) {
        $router->get('/', 'BrandController@index');
        $router->post('/', 'BrandController@store');
        $router->get('/{id}', 'BrandController@show');
        $router->post('/{id}', 'BrandController@update');
        $router->post('/delete/bulk', 'BrandController@destroy');
    });

    /**
     * Product Transfer Stock
     */
    $router->group(['prefix' => 'transfer-stock'], function ($router) {
        $router->get('/', 'TransferStockController@index');
        $router->post('/', 'TransferStockController@store');
        $router->get('/{id}', 'TransferStockController@show');
        $router->post('/{id}', 'TransferStockController@update');
        $router->post('/delete/bulk', 'TransferStockController@destroy');

        $router->get('/list/product', 'TransferStockController@listProduct');
        $router->get('/list/ingredient', 'TransferStockController@listIngredient');
        $router->get('/list/branch', 'TransferStockController@listBranch');
    });

    /**
     * Product Stock Adjustment
     */
    $router->group(['prefix' => 'adjustment-stock'], function ($router) {
        $router->get('/', 'ProductStockAdjustmentController@index');
        $router->post('/', 'ProductStockAdjustmentController@store');
        $router->get('/{id}', 'ProductStockAdjustmentController@show');
        $router->post('/delete/bulk', 'ProductStockAdjustmentController@destroy');

        $router->get('/list/product', 'ProductStockAdjustmentController@listProduct');
    });

    /**
     * Product Return
     */
    $router->group(['prefix' => 'product-return'], function ($router) {
        $router->get('/', 'ProductReturnController@index');
        $router->post('/', 'ProductReturnController@store');
        $router->get('/{id}', 'ProductReturnController@show');
        $router->post('/{id}', 'ProductReturnController@update');
        $router->post('/delete/bulk', 'ProductReturnController@destroy');

        $router->get('/list/product', 'ProductReturnController@listProduct');
    });

    /**
     * Product Donation
     */
    $router->group(['prefix' => 'product-donation'], function ($router) {
        $router->get('/', 'ProductReturnController@index');
        $router->post('/', 'ProductReturnController@store');
        $router->get('/{id}', 'ProductReturnController@show');
        $router->post('/{id}', 'ProductReturnController@update');
        $router->post('/delete/bulk', 'ProductReturnController@destroy');

        $router->get('/list/product', 'ProductReturnController@listProduct');
    });

    /**
     * Product Packaging
     */
    $router->group(['prefix' => 'product-packaging'], function ($router) {
        $router->get('/', 'PackagingController@index');
        $router->post('/', 'PackagingController@store');
        $router->get('/{id}', 'PackagingController@show');
        $router->post('/{id}', 'PackagingController@update');
        $router->post('/delete/bulk', 'PackagingController@destroy');
        $router->delete('/{id}', 'PackagingController@delete');

        $router->get('/list/product', 'PackagingController@listProduct');
        $router->get('/list/product-ingredient', 'PackagingController@listProductIngredient');
        $router->get('/list/division', 'PackagingController@listDivision');
        $router->get('/list/packaging', 'PackagingController@listPackaging');
    });
});
