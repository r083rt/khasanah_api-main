<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Product;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Models\Reporting\MonitoringClosingSummaryProduct;

class RemainsStock extends Job
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
                    'remains_closing' => $qty
                ]);
            } else {
                MonitoringClosingSummaryProduct::create([
                    'monitoring_closing_summary_id' => $summary->id,
                    'product_id' => $productId,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branchId,
                    'date' => $date,
                    'remains_closing' => $qty
                ]);
            }

           $remains_closing = MonitoringClosingSummaryProduct::where([
                'monitoring_closing_summary_id' => $summary->id,
                'branch_id' => $branchId,
                'date' => $date,
            ])->sum('remains_closing');

            $summary->update([
                'remains_closing' => $remains_closing
            ]);

            dispatch(new DiffHpp([
                'product_id' => $productId,
                'branch_id' => $branchId,
            ]));
        }
    }
}
