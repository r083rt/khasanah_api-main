<?php

namespace App\Imports;

use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductIngredientImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $key => $row) {
            if ($key > 0) {
                $unit = ProductRecipeUnit::where('name', $row[3])->first();
                if ($unit) {
                    $unitValue = $unit->id;
                } else {
                    $unit = ProductRecipeUnit::create(['name' => $row[3]]);
                    $unitValue = $unit->id;
                }

                $unitDelivery = ProductRecipeUnit::where('name', $row[4])->first();
                if ($unitDelivery) {
                    $unitDeliveryValue = $unitDelivery->id;
                } else {
                    $unitDelivery = ProductRecipeUnit::create(['name' => $row[4]]);
                    $unitDeliveryValue = $unitDelivery->id;
                }

                ProductIngredient::updateOrCreate(
                    [
                        'code' => $row[0]
                    ],
                    [
                        'code' => $row[0],
                        'barcode' => $row[1],
                        'name' => $row[2],
                        'product_recipe_unit_id' => $unitValue,
                        'product_ingredient_unit_delivery_id' => $unitDeliveryValue,
                    ]
                );
            }
        }
    }
}
