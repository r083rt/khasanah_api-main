<?php

namespace App\Services\Reporting;

use App\Models\Inventory\ProductReturn;

class ReturService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        $data = ProductReturn::select('branch_id', 'product_id', 'qty', 'type', 'created_at', 'total_price', 'total_hpp')->with(['product:id,name,price,code,product_category_id', 'branch:id,name', 'product.category:id,name'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($branchId) {
            $data = $data->where('branch_id', $branchId);
        }
        $data = $data->get();
        foreach ($data as $value) {
            $value->price = $value->product ? $value->product->price : 0;
        }

        return $data;
    }
}
