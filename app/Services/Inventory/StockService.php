<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductStockLog;
use App\Models\Inventory\ProductStockLogTemp;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StockService
{
    /**
     * Get all Stock
     */
    public function getStock($productId, $branchId)
    {
        $key = 'product-stock-' . $productId . '-' . $branchId;
        // if (!Cache::has($key)) {
        $data = ProductStock::select('stock')->where('product_id', $productId)->where('branch_id', $branchId)->first();
        if ($data) {
            $data = $data->toArray();
        }
            // Cache::put($key, $data, 86400);
        // } else {
        //     $data = Cache::get($key);
        // }

        return $data;
    }

    /**
     * Create Stock Log
     */
    public function createStockLog($data)
    {
        if ( $data['stock'] != 0) {
            $cek = ProductStockLog::where([
                'branch_id' => $data['branch_id'],
                'product_id' => $data['product_id'],
                'stock' => $data['stock'],
                'table_reference' => $data['table_reference'],
                'table_id' => $data['table_id'],
                'from' => $data['from'],
            ])->whereDate('created_at', date('Y-m-d'))->count();
            if ($cek == 0) {
                return ProductStockLog::create($data);
            }
        }
    }

    /**
     * Create Stock
     */
    public function create($productId, $branchId, $stock, $from, $tableReference, $tableId, $dateNext = null, $createdBy = null)
    {
        if ($dateNext) {
            if ($stock > 0) {
                ProductStockLogTemp::create([
                    'date' => $dateNext,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'stock' => $stock,
                    'from' => $from,
                    'table_reference' => $tableReference,
                    'table_id' => $tableId,
                    'created_by' => $createdBy,
                ]);
            }
        } else {
            if ($productStock = ProductStock::where('product_id', $productId)->where('branch_id', $branchId)->first()) {
                $oldStock = $productStock->stock;
                $productStock->update([
                    'stock' => $oldStock + ($stock)
                ]);
            } else {
                ProductStock::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'stock' => $stock,
                ]);
                $oldStock = 0;
            }

            $this->createStockLog([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'stock' => $stock,
                'stock_old' => $oldStock,
                'from' => $from,
                'table_reference' => $tableReference,
                'table_id' => $tableId,
            ]);

            if ($from == 'Penyesuain Stok') {
                $dataMapping = config('inventory.mapping_adjustment_stock');
                if ($dataMapping && isset($dataMapping[$productId])) {
                    foreach ($dataMapping[$productId] as $value) {
                        $this->create($value, $branchId, $stock * -1, $from, $tableReference, $tableId);
                    }
                }
            }
        }
    }
}
