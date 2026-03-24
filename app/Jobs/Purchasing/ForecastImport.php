<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchasing\ForecastImport as PurchasingForecastImport;
use Illuminate\Support\Facades\Log;

class ForecastImport extends Job
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
        $this->onQueue('forecast_import');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    // public function handle()
    // {
    //     $id = $this->data['id'];
    //     Log::info('JOB START', $this->data);
    //     $branch_name = $this->data['branch_name'];
    //     $product_code = $this->data['product_code'];

    //     $reason = '';
    //     $branch = Branch::where('name', $branch_name)->first();
    //     if ($branch) {
    //         $branch_id = $branch->id;
    //     } else {
    //         $branch_id = null;
    //         $reason .= 'Cabang tidak ditemukan. ';
    //     }

    //     $product = Product::where('code', $product_code)->first();
    //     if ($product) {
    //         $product_id = $product->id;
    //         $product_name = $product->name;
    //     } else {
    //         $product_id = null;
    //         $product_name = null;
    //         $reason .= 'Produk tidak ditemukan. ';
    //     }

    //     PurchasingForecastImport::where('id', $id)->update([
    //         'branch_id' => $branch_id,
    //         'product_id' => $product_id,
    //         'product_name' => $product_name,
    //         'reason' => $reason,
    //         'is_valid' => $reason ? 0 : 1,
    //     ]);
    //     Log::info('JOB START', $this->data);
    // }
    public function handle()
    {
        try {
            Log::info('JOB START', $this->data);

            $id = $this->data['id'];
            $branch_name = $this->data['branch_name'];
            $product_code = $this->data['product_code'];

            $reason = '';

            $branch = Branch::where('name', $branch_name)->first();
            if ($branch) {
                $branch_id = $branch->id;
            } else {
                $branch_id = null;
                $reason .= 'Cabang tidak ditemukan. ';
            }

            $product = Product::where('code', $product_code)->first();
            if ($product) {
                $product_id = $product->id;
                $product_name = $product->name;
            } else {
                $product_id = null;
                $product_name = null;
                $reason .= 'Produk tidak ditemukan. ';
            }

            PurchasingForecastImport::where('id', $id)->update([
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'reason' => $reason,
                'is_valid' => $reason ? 0 : 1,
            ]);

            Log::info('JOB END', ['id' => $id]);
        } catch (\Exception $e) {
            Log::error('JOB ERROR', [
                'message' => $e->getMessage(),
                'data' => $this->data
            ]);

            throw $e; // penting biar masuk failed_jobs
        }
    }
}
