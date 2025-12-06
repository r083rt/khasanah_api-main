<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductIngredientStock;
use App\Models\Inventory\ProductIngredientStockLog;
use App\Models\Inventory\ProductStockLog;
use Illuminate\Support\Facades\Auth;

class IngredientStockService
{
    /**
     * Create Stock
     */
    protected function create($productIngredientId, $branchId, $productRecipeUnitId, $stock, $tableId, $from, $tableReference, $createdBy, $isReplace = false)
    {
        if (
            $productIngredient = ProductIngredientStock::where('product_ingredient_id', $productIngredientId)
                ->where('branch_id', $branchId)
                ->where('product_recipe_unit_id', $productRecipeUnitId)
                ->first()
        ) {
            $oldStock = $productIngredient->stock;
            if ($isReplace) {
                $productIngredient->update([
                    'stock' => $stock
                ]);
            } else {
                $productIngredient->update([
                    'stock' => $oldStock + ($stock)
                ]);
            }
        } else {
            $data = [
                'branch_id' => $branchId,
                'product_ingredient_id' => $productIngredientId,
                'product_recipe_unit_id' => $productRecipeUnitId,
                'stock' => $stock,
            ];
            if ($createdBy) {
                $data['created_by'] = Auth::id();
            }

            ProductIngredientStock::create($data);
            $oldStock = 0;
        }

        $this->createStockLog([
            'branch_id' => $branchId,
            'product_ingredient_id' => $productIngredientId,
            'product_recipe_unit_id' => $productRecipeUnitId,
            'stock' => $isReplace ? ($stock - $oldStock) : $stock,
            'stock_old' => $oldStock,
            'from' => $from,
            'table_reference' => $tableReference,
            'table_id' => $tableId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Create Stock Log
     */
    public function createStockLog($data)
    {
        if ($data['stock'] != 0) {
            // $cek = ProductIngredientStockLog::where([
            //     'branch_id' => $data['branch_id'],
            //     'product_ingredient_id' => $data['product_ingredient_id'],
            //     'product_recipe_unit_id' => $data['product_recipe_unit_id'],
            //     'stock' => $data['stock'],
            //     'table_reference' => $data['table_reference'],
            //     'table_id' => $data['table_id'],
            //     'from' => $data['from'],
            // ])->whereDate('created_at', date('Y-m-d'))->count();
            // if ($cek == 0) {
            //     return ProductIngredientStockLog::create($data);
            // }
            return ProductIngredientStockLog::create($data);
        }
    }

    /**
     * Create from Po Supplier
     */
    public function createFromPoSupplier($productIngredientId, $branchId, $productRecipeUnitId, $stock, $tableId, $createdBy = null)
    {
        $from = "Po Supplier";
        $tableReference = "po_supplier_details";
        $this->create($productIngredientId, $branchId, $productRecipeUnitId, $stock, $tableId, $from, $tableReference, $createdBy);
    }

    /**
     * Create from Stock Opname
     */
    public function createFromStockOpname($productIngredientId, $branchId, $productRecipeUnitId, $stock, $tableId, $isReplace = false, $createdBy = null)
    {
        $from = "Stock Opname";
        $tableReference = "stock_opname_ingredient_details";
        $this->create($productIngredientId, $branchId, $productRecipeUnitId, $stock, $tableId, $from, $tableReference, $createdBy, $isReplace);
    }
}
