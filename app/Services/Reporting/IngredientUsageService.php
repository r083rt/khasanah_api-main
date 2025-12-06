<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Reporting\IngredientUsage;
use App\Models\Reporting\IngredientUsageStatus;

class IngredientUsageService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;
        $product_category_id = $request->product_category_id;

        if ($branch) {
            $branchIds = [$branch];
        } else {
            $branchIds = Branch::select('id')->pluck('id');
        }

        $data = IngredientUsage::whereIn('branch_id', $branchIds)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate);

        if ($product_category_id) {
            $data = $data->where('product_category_id', $product_category_id);
        }

        return $data->get();
    }

    /**
     * checking
     */
    public function checking($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;

        if ($branch) {
            $branchIds = [$branch];
        } else {
            $branchIds = Branch::select('id')->pluck('id');
        }

        $datas = IngredientUsageStatus::whereIn('branch_id', $branchIds)->where('date', '>=', $startDate)->where('date', '<=', $endDate)->get();
        $result = true;
        foreach ($datas as $value) {
            if ($value->status_po_production_cookie == 'new' || $value->status_po_production_brownies == 'new') {
                $result = false;
            }
        }

        return $result;
    }
}
