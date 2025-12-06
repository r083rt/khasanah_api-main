<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Services\Reporting\MonitoringClosingService;

class FillData extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = $this->data['date'];
        $branchId = $this->data['branch_id'];
        MonitoringClosingSummary::where('branch_id', $branchId)->where('date',$date)->delete();

        $productCategories = ProductCategory::select('id', 'name')->get();
        $productsAll = Product::select('id', 'name', 'product_category_id')
                    ->orderBy('name')
                    ->get();

        $datas = [];
        foreach ($productCategories as $category) {
            $products = $productsAll->where('product_category_id', $category->id);
            $firstStock = 0;
            $in = 0;
            $sale = 0;
            $order = 0;
            $return = 0;
            $transferStock = 0;
            $remainsClosing = 0;
            $difference = 0;
            $hppTotal = 0;
            foreach ($products as $rows) {

                $checkFirstStock = MonitoringClosingService::firstStock($date, $branchId, $rows->id);
                $firstStock += $checkFirstStock;

                $checkIn = MonitoringClosingService::in($date, $branchId, $rows->id);
                $in += $checkIn;

                $checkSale = MonitoringClosingService::sale($date, $branchId, $rows->id);
                $sale += $checkSale;

                $checkOrder = MonitoringClosingService::order($date, $branchId, $rows->id);
                $order += $checkOrder;

                $checkReturn = MonitoringClosingService::return($date, $branchId, $rows->id);
                $return += $checkReturn;

                $checkTransferStock = MonitoringClosingService::transferStock($date, $branchId, $rows->id);
                $transferStock += $checkTransferStock;

                $checkRemainsClosing = MonitoringClosingService::remainsClosing($date, $branchId, $rows->id);
                $remainsClosing += $checkRemainsClosing;

                $checkDifference = MonitoringClosingService::difference($checkFirstStock, $checkIn, $checkSale, $checkOrder, $checkReturn, $checkTransferStock, $checkRemainsClosing);
                $difference += $checkDifference;

                if ($checkDifference != 0) {
                    $hppTotal += MonitoringClosingService::hppTotal($rows->id, $checkDifference);
                }
            }

            $datas[] = [
                'product_category_id' => $category->id,
                'type' => $category->name,
                'first_stock' => $firstStock,
                'in' => $in,
                'sale' => $sale,
                'order' => $order,
                'return' => $return,
                'transfer_stock' => $transferStock,
                'remains_closing' => $remainsClosing,
                'difference' => $difference,
                'branch_id' => $branchId,
                'date' => $date,
                'hpp_total' => $difference == 0 ? 0 : $hppTotal,
            ];
        }

        MonitoringClosingSummary::insert($datas);
    }
}
