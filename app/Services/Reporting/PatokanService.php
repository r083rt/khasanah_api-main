<?php

namespace App\Services\Reporting;

use App\Models\Purchasing\ReceivePoSupplierDetail;
use App\Models\ProductIngredient;

class PatokanService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $product_ingredient_ids = ReceivePoSupplierDetail::distinct('product_ingredient_id')->pluck('product_ingredient_id');

        $productIngredients = ProductIngredient::select('id', 'name', 'product_recipe_unit_id')->whereIn('id', $product_ingredient_ids)->orderBy('name')->get();

        $patokan_details = [];

        foreach ($productIngredients as $value) {
            $real_price_sum = ReceivePoSupplierDetail::select('real_price', 'receive_id')
                                                     ->with(["receivePoSupplier" => function($q) use($startDate, $endDate) {
                                                        $q->where('received_at', '>=', $startDate);
                                                        $q->where('received_at', '<=', $endDate);
                                                    }])
                                                     ->where('product_ingredient_id', $value->id)
                                                     ->sum('real_price');

            $price_prediction_sum = ReceivePoSupplierDetail::select('price', 'receive_id')
                                                     ->with(["receivePoSupplier" => function($q) use($startDate, $endDate) {
                                                        $q->where('received_at', '>=', $startDate);
                                                        $q->where('received_at', '<=', $endDate);
                                                    }])
                                                     ->where('product_ingredient_id', $value->id)
                                                     ->sum('price');
            $patokan_details[] = [
                'productingredient_name' => $value->name,
                'real_price' => $real_price_sum,
                'price_prediction' => $price_prediction_sum
            ];
        }

        return $patokan_details;
    }
}
