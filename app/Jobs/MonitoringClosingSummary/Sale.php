<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Product;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Models\Reporting\MonitoringClosingSummaryProduct;

class Sale extends Job
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
        $date = date('Y-m-d');

        $branchId = $this->data['branch_id'];
        $productId = $this->data['product_id'];
        $qty = $this->data['qty'];

        $product = Product::find($productId);
        $product_category_id = $product ? $product->product_category_id : null;
        $summary = MonitoringClosingSummary::where('date', $date)->where('branch_id', $branchId)->where('product_category_id', $product_category_id)->first();
        if ($summary) {
            $summary->update([
                'sale' => $summary->sale + $qty
            ]);

            $summaryProduct = MonitoringClosingSummaryProduct::where([
                'monitoring_closing_summary_id' => $summary->id,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'date' => $date,
            ])->first();
            if ($summaryProduct) {
                $summaryProduct->update([
                    'monitoring_closing_summary_id' => $summary->id,
                    'product_id' => $productId,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branchId,
                    'date' => $date,
                    'sale' => $summaryProduct->sale + $qty
                ]);
            } else {
                MonitoringClosingSummaryProduct::create([
                    'monitoring_closing_summary_id' => $summary->id,
                    'product_id' => $productId,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branchId,
                    'date' => $date,
                    'sale' => $qty
                ]);
            }

            dispatch(new DiffHpp([
                'product_id' => $productId,
                'branch_id' => $branchId,
            ]));
        }
    }
}
