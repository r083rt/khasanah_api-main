<?php

namespace App\Jobs\MonitoringClosingSummary;

use App\Jobs\Job;
use App\Models\Product;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Models\Reporting\MonitoringClosingSummaryProduct;
use Illuminate\Support\Facades\Log;

class DiffHpp extends Job
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

        $summary = MonitoringClosingSummaryProduct::where('date', $date)->where('branch_id', $branchId)->where('product_id', $productId)->first();
        if ($summary) {
            $diff = (int) $summary->remains_closing - ($summary->first_stock + $summary->in - $summary->sale - $summary->order - $summary->return - $summary->transfer_stock);
            $hppTotal = 0;
            if ($diff != 0) {
                $hppTotal = Product::where('id', $productId)->sum('price_sale') * $diff;
            }
            $summary->update([
                'difference' => $diff,
                'hpp_total' => $hppTotal,
            ]);

            $product = Product::find($productId);
            $product_category_id = $product ? $product->product_category_id : null;
            $summary = MonitoringClosingSummary::where('date', $date)->where('branch_id', $branchId)->where('product_category_id', $product_category_id)->first();
            if ($summary) {
                $summaryProduct = MonitoringClosingSummaryProduct::select('difference', 'hpp_total')->where('monitoring_closing_summary_id', $summary->id)->get();
                $summary->update([
                    'difference' => $summaryProduct->sum('difference'),
                    'hpp_total' => $summaryProduct->sum('hpp_total'),
                ]);
            }
        }
    }
}
