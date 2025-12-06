<?php

namespace Database\Seeders;

use App\Models\ProductRecipe;
use Illuminate\Database\Seeder;

class ProductRecipeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductRecipe::create([
            'product_id' => 1,
            'product_ingredient_id' => 1,
            'product_recipe_unit_id' => 1,
            'measure' => 1,
            'created_by' => 1,
        ]);

        ProductRecipe::create([
            'product_id' => 1,
            'product_ingredient_id' => 2,
            'product_recipe_unit_id' => 1,
            'measure' => 2,
            'created_by' => 1,
        ]);
    }
}
