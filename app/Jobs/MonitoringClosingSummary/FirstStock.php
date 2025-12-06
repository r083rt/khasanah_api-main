<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Models\Reporting\MonitoringClosingSummaryProduct;

class FirstStock extends Job
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
        $this->onQueue('monitoring_closing_summary');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = date('Y-m-d');
        $date = date('Y-m-d', strtotime('+1 day', strtotime($now)));

        $branchId = $this->data['branch_id'];
        $products = $this->data['products'];

        $productCategories = ProductCategory::select('id', 'name')->get();

        MonitoringClosingSummary::where([
            'branch_id' => $branchId,
            'date' => $date,
        ])->delete();

        $result = [];
        foreach ($productCategories as $category) {
            $result[] = [
                'product_category_id' => $category->id,
                'type' => $category->name,
                'first_stock' => 0,
                'in' => 0,
                'sale' => 0,
                'order' => 0,
                'return' => 0,
                'transfer_stock' =>0,
                'remains_closing' => 0,
                'difference' => 0,
                'branch_id' => $branchId,
                'date' => $date,
                'hpp_total' => 0,
            ];
        }

        MonitoringClosingSummary::insert($result);

        MonitoringClosingSummaryProduct::where([
            'branch_id' => $branchId,
            'date' => $date,
        ])->delete();

        $datas = [];
        foreach ($products as $value) {
            $product = Product::find($value['id']);
            $product_category_id = $product ? $product->product_category_id : null;
            $summary = MonitoringClosingSummary::where('date', $date)->where('branch_id', $branchId)->where('product_category_id', $product_category_id)->first();
            if ($summary) {
                $summary->update([
                    'first_stock' => $summary->first_stock + $value['stock_real']
                ]);

                $datas[] = [
                    'monitoring_closing_summary_id' => $summary ? $summary->id : null,
                    'product_category_id' => $product_category_id,
                    'product_id' => $value['id'],
                    'branch_id' => $branchId,
                    'date' => $date,
                    'first_stock' => $value['stock_real'],
                ];
            }
        }

        MonitoringClosingSummaryProduct::insert($datas);

        foreach ($products as $value) {
            dispatch(new DiffHpp([
                'product_id' => $value['id'],
                'branch_id' => $branchId,
            ]));
        }
    }
}
