<?php

namespace App\Jobs;

use App\Models\ProductStock;
use App\Services\Inventory\StockService;

class StockLogJob extends Job
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
        $productID = $this->data['product_id'];
        $stock = $this->data['stock'];
        $tableId = $this->data['table_id'];
        $stock_real = $this->data['stock_real'];
        $stock_old = $this->data['stock_old'];
        $branchId = $this->data['branch_id'];
        $userId = $this->data['user_id'];
        $from = $this->data['from'];
        $table_reference = $this->data['table_reference'];

        ProductStock::updateOrCreate(
            [
                'product_id' => $productID,
                'branch_id' => $branchId,
            ],
            [
                'product_id' => $productID,
                'branch_id' => $branchId,
                'stock' => $stock_real
            ]
        );

        $stockService = app(StockService::class);
        $stockService->createStockLog([
            'branch_id' => $branchId,
            'product_id' => $productID,
            'stock' => $stock,
            'stock_old' => $stock_old,
            'from' => $from,
            'table_reference' => $table_reference,
            'table_id' => $tableId,
            'created_by' => $userId,
        ]);
    }
}
