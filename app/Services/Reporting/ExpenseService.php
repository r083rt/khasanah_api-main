<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Pos\Expense;

class ExpenseService
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

        $data = Expense::with(['branch:id,name', 'createdBy:id,name', 'master:id,nomor,name'])->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
        if ($branchIds) {
            $data = $data->whereIn('branch_id', $branchIds);
        }

        return $data->get();
    }
}
