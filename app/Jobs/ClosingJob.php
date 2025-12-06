<?php

namespace App\Jobs;

use App\Models\Pos\ClosingDetail;
use App\Models\Pos\ClosingProduct;
use App\Models\Product;

class ClosingJob extends Job
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
        $products = $this->data['products'];
        $closingId = $this->data['closing_id'];
        $branchId = $this->data['branch_id'];
        $userId = $this->data['user_id'];

        foreach ($products as $value) {
            $difference = ($value['stock_system'] - $value['stock_real']) * -1;
            $allData = [
                'product_id' => $value['id'],
                'stock_real' => $value['stock_real'],
                'table_id' => $closingId,
                'stock' => $difference,
                'stock_old' => $value['stock_system'],
                'branch_id' => $branchId,
                'user_id' => $userId,
                'from' => 'Closing',
                'table_reference' => 'closings',
            ];
            dispatch(new StockLogJob($allData));
        }

        foreach ($products as $value) {
            $difference = ($value['stock_system'] - $value['stock_real']) * -1;
            $product = Product::select('id', 'name', 'code')->find($value['id']);
            ClosingProduct::create([
                'closing_id' => $closingId,
                'product_id' => $value['id'],
                'product_name' => $product->name,
                'product_code' => $product->code,
                'stock_system' => $value['stock_system'],
                'stock_real' => $value['stock_real'],
                'difference' => $difference,
                'note' => $value['note'],
            ]);
        }
    }
}
