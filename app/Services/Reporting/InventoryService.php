<?php

namespace App\Services\Reporting;

use App\Models\Purchasing\PoSupplier;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ReceivePoSupplierDetail;
use App\Models\Purchasing\ReturnSuppliersDetail;

class InventoryService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $product_ingredient_ids = ReceivePoSupplierDetail::distinct('product_ingredient_id')->pluck('product_ingredient_id');

        $productIngredients = ProductIngredient::select('id', 'name', 'product_recipe_unit_id', 'product_ingredient_unit_delivery_id', 'unit_value')->with(['unit', 'unitDelivery'])->whereIn('id', $product_ingredient_ids)->orderBy('name')->get();

        $patokan_details = [];

        foreach ($productIngredients as $value) {
            $recive_qty_sum = ReceivePoSupplierDetail::select('qty', 'receive_id')
                                                    ->where('product_ingredient_id', $value->id)
                                                    ->whereHas('receivePoSupplier', function ($query) use ($startDate, $endDate) {
                                                        $query->where('received_at', '>=', $startDate)
                                                            ->where('received_at', '<=', $endDate);
                                                    })
                                                    ->with(["receivePoSupplier" => function($q) use($startDate, $endDate) {
                                                        $q->where('received_at', '>=', $startDate)
                                                        ->where('received_at', '<=', $endDate);
                                                    }])
                                                    ->sum('qty');

            $recive_qty_sum_unit = ProductIngredient::getTotalUnit($recive_qty_sum, $value->unit_value);

            $return_qty_sum = ReturnSuppliersDetail::select('qty', 'return_supplier_id')
                                                    ->whereHas('returnSupplier', function ($query) use ($startDate, $endDate) {
                                                        $query->where('return_at', '>=', $startDate)
                                                            ->where('return_at', '<=', $endDate);
                                                    })
                                                    ->with(["returnSupplier" => function($q) use($startDate, $endDate) {
                                                        $q->where('return_at', '>=', $startDate)
                                                        ->where('return_at', '<=', $endDate);
                                                    }])
                                                     ->where('product_ingredient_id', $value->id)
                                                     ->sum('qty');

            $return_qty_sum_unit = ProductIngredient::getTotalUnit($return_qty_sum, $value->unit_value);

            $patokan_details[] = [
                'productingredient_name' => $value->name,
                'unit' => $value->unit->name,
                'delivery_unit' => $value->unitDelivery->name,
                'receive_sum' => $recive_qty_sum,
                'return_sum' => $return_qty_sum,
                'receive_sum_unit' => $recive_qty_sum_unit,
                'return_sum_unit' => $return_qty_sum_unit
            ];
        }


        return $patokan_details;
    }
}
