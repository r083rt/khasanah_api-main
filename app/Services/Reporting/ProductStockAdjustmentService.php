<?php

namespace App\Services\Reporting;

use App\Exports\Reporting\ProductStockAdjustment as ReportingProductStockAdjustment;
use App\Models\Branch;
use App\Models\Inventory\ProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;

class ProductStockAdjustmentService
{
    /**
     * Get ProductStockAdjustmentService service all
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

        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual',
            'Po Brownis',
            'Po Brownis Toko'
        ];

        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'from', 'created_by', 'created_at')
            ->with(['product:id,name,code,product_category_id,gramasi', 'branch:id,name', 'createdBy:id,name', 'product.category:id,name'])
            ->whereIn('from', $from)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($branchIds) {
            $data = $data->whereIn('branch_id', $branchIds);
        }

        $data = $data->get();
        foreach ($data as $value) {
            $gramasi = $value->product ? $value->product->gramasi : null;
            $value->gramasi_conversion = $value->stock * $gramasi;
        }

        return $data;
    }
}
