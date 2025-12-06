<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Pos\ClosingProduct;

class StockService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;
        $territory = $request->territory_id;

        if ($territory) {
            if ($branch) {
                $branchIds = [$branch];
            } else {
                $branchIds = Branch::select('id')->where('territory_id', $territory)->pluck('id');
            }
        } else {
            $branchIds = null;
        }

        $data = ClosingProduct::with(['closing', 'closing.createdBy:id,name', 'product:id,price,product_category_id', 'product.category:id,name', 'closing.branch:id,name'])->whereHas('closing', function ($query) use ($branch, $startDate, $endDate, $branchIds, $territory) {
            $query = $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
            if ($branchIds) {
                $query = $query->whereIn('branch_id', $branchIds);
            }
        });

        return $data->get();
    }
}
