<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Branch;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Purchasing\StockOpnameImport as PurchasingStockOpnameImport;
use App\Models\ProductIngredient;

class StockOpnameImport extends Job
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
        $this->onQueue('so_import');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id = $this->data['id'];
        $branch_id = $this->data['branch_id'];
        $product_ingredient_id = $this->data['product_ingredient_id'];
        $product_recipe_unit_id_1 = $this->data['product_recipe_unit_id_1'];
        $product_recipe_unit_id_2 = $this->data['product_recipe_unit_id_2'];
        $product_recipe_unit_id_3 = $this->data['product_recipe_unit_id_3'];

        $is_valid = 1;
        $reason = '';

        $product_ingredient_name = null;
        $productIngredient = ProductIngredient::select('id', 'name')->find($product_ingredient_id);
        if (is_null($productIngredient)) {
            $is_valid = 0;
            $reason .= 'Bahan tidak valid. ';
        } else {
            $product_ingredient_name = $productIngredient->name;
        }

        $branch_name = null;
        $branch = Branch::select('id', 'name')->find($branch_id);
        if (is_null($branch)) {
            $is_valid = 0;
            $reason .= 'Cabang tidak valid. ';
        } else {
            $branch_name = $branch->name;
        }

        $product_recipe_unit_1_name = null;
        if ($product_recipe_unit_id_1) {
            $productRecipeUnit = ProductRecipeUnit::select('id', 'name')->find($product_recipe_unit_id_1);
            if (is_null($productRecipeUnit)) {
                $is_valid = 0;
                $reason .= 'Satuan 1 tidak valid. ';
            } else {
                $product_recipe_unit_1_name = $productRecipeUnit->name;
            }
        }

        $product_recipe_unit_2_name = null;
        if ($product_recipe_unit_id_2) {
            $productRecipeUnit = ProductRecipeUnit::select('id', 'name')->find($product_recipe_unit_id_2);
            if (is_null($productRecipeUnit)) {
                $is_valid = 0;
                $reason .= 'Satuan 2 tidak valid. ';
            } else {
                $product_recipe_unit_2_name = $productRecipeUnit->name;
            }
        }

        $product_recipe_unit_3_name = null;
        if ($product_recipe_unit_id_3) {
            $productRecipeUnit = ProductRecipeUnit::select('id', 'name')->find($product_recipe_unit_id_3);
            if (is_null($productRecipeUnit)) {
                $is_valid = 0;
                $reason .= 'Satuan 3 tidak valid. ';
            } else {
                $product_recipe_unit_3_name = $productRecipeUnit->name;
            }
        }

        PurchasingStockOpnameImport::where('id', $id)->update([
            'branch_name' => $branch_name,
            'product_ingredient_name' => $product_ingredient_name,
            'product_recipe_unit_1_name' => $product_recipe_unit_1_name,
            'product_recipe_unit_2_name' => $product_recipe_unit_2_name,
            'product_recipe_unit_3_name' => $product_recipe_unit_3_name,
            'is_valid' => $is_valid,
            'reason' => $reason,
        ]);
    }
}
