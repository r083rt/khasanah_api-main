<?php

namespace App\Jobs;

use App\Models\ProductStock;
use App\Services\Inventory\StockService;

class CreateStockLog extends Job
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
        $this->onQueue('cashier');
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
        $branchId = $this->data['branch_id'];
        $from = $this->data['from'];
        $table_reference = $this->data['table_reference'];
        $userId = $this->data['user_id'];

        $productStock = ProductStock::where('product_id', $productID)->where('branch_id', $branchId)->first();
        if ($productStock) {
            $oldStock = $productStock->stock;
            $productStock->update([
                'stock' => $oldStock + ($stock)
            ]);

            $stockService = app(StockService::class);
            $stockService->createStockLog([
                'branch_id' => $branchId,
                'product_id' => $productID,
                'stock' => $stock,
                'stock_old' => $oldStock,
                'from' => $from,
                'table_reference' => $table_reference,
                'table_id' => $tableId,
                'created_by' => $userId,
            ]);
        }
    }
}
