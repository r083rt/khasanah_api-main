<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Inventory\ProductStockLog;
use App\Models\Pos\ClosingProduct;

class MutationStockService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $branchId = $request->branch_id;
        $productId = $request->product_id;

        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'stock_old', 'from', 'created_by', 'created_at')->with(['product:id,name', 'branch:id,name', 'createdBy:id,name'])
        ->whereDate('created_at', $startDate);

        if ($productId) {
            $data = $data->where('product_id', $productId);
        }

        if ($branchId) {
            $data = $data->where('branch_id', $branchId);
        }

        $data = $data->orderBy('created_at')->get();
        $stock_after = 0;
        $created_by = '';
        $created_at = '';
        $product = '';
        $branch = '';
        foreach ($data as $key => $value) {
            if ($key == $data->count() - 1) {
                $stock_after = $value->stock_after;
                $created_by = $value->createdBy ?  $value->createdBy->name : null;
                $created_at = $value->created_at;
                $product = $value->product ? $value->product->name : null;
                $branch =  $value->branch ? $value->branch->name : null;
            }
        }

        if ($data->count() > 0) {
            $data->push([
                'stock_after' => $stock_after,
                'from' => 'Current Stock',
                'stock' => 0,
                'stock_old' => $stock_after,
                'created_at' => $created_at,
                'created_by' => [
                    'name' => $created_by
                ],
                'branch' => [
                    'name' => $branch
                ],
                'product' => [
                    'name' => $product
                ],
            ]);
        }

        return $data;
    }
}
