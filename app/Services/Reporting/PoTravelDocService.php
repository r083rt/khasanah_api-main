<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Distribution\PoSjItem;
use App\Models\Pos\ClosingProduct;
use App\Models\Product;
use App\Models\ProductIngredient;

class PoTravelDocService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;

        $data = PoSjItem::with(['posj', 'branch:id,name', 'product:id,name,product_category_id', 'product.category:id,name'])->whereHas('posj', function ($query) use ($branch, $startDate, $endDate) {
            $query = $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
        });
        if ($branch) {
            $data = $data->where('branch_id', $branch);
        }

        $products = Product::select('id', 'unit_value')->whereIn('id', $data->pluck('product_id'))->get();
        $productIngredients = ProductIngredient::select('id', 'unit_value')->whereIn('id', $data->pluck('product_ingredient_id'))->get();

        $data = $data->get();
        foreach ($data as $value) {
            if ($value->product_id) {
                $product = $products->where('id', $value->product_id)->first();
                if ($product) {
                    if ($product->unit_value == 0 || is_null($product->unit_value)) {
                        $value->qty_unit_delivery = $value->qty;
                    } else {
                        $qty = $value->qty / $product->unit_value;
                        $value->qty_unit_delivery = $qty;
                    }
                } else {
                    $value->qty_unit_delivery = null;
                }
            } else {
                $product = $productIngredients->where('id', $value->product_ingredient_id)->first();
                if ($product) {
                    if ($product->unit_value == 0 || is_null($product->unit_value)) {
                        $value->qty_unit_delivery = $value->qty;
                    } else {
                        $qty = $value->qty / $product->unit_value;
                        $value->qty_unit_delivery = $qty;
                    }
                } else {
                    $value->qty_unit_delivery = null;
                }
            }
        }
        return $data;
    }
}
