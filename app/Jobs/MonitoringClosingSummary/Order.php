<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Order as ModelsOrder;
use App\Models\Product;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Models\Reporting\MonitoringClosingSummaryProduct;

class Order extends Job
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

        $orderId = $this->data['order_id'];
        $order = ModelsOrder::with('products')->find($orderId);
        if ($order) {
            $branchId = $order->branch_id;
            foreach ($order->products as $value) {
                $productId = $value->product_id;

                $product = Product::find($value->product_id);
                $product_category_id = $product ? $product->product_category_id : null;
                $summary = MonitoringClosingSummary::where('date', $date)->where('branch_id', $branchId)->where('product_category_id', $product_category_id)->first();
                if ($summary) {
                    $summary->update([
                        'order' => $summary->order + $value->qty
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
                            'order' => $summaryProduct->order + $value->qty
                        ]);
                    } else {
                        MonitoringClosingSummaryProduct::create([
                            'monitoring_closing_summary_id' => $summary->id,
                            'product_id' => $productId,
                            'product_category_id' => $product_category_id,
                            'branch_id' => $branchId,
                            'date' => $date,
                            'order' => $value->qty
                        ]);
                    }

                    dispatch(new DiffHpp([
                        'product_id' => $productId,
                        'branch_id' => $branchId,
                    ]));
                }
            }

        }
    }
}
