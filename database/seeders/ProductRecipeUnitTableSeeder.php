<?php

namespace Database\Seeders;

use App\Models\Inventory\ProductRecipeUnit;
use Illuminate\Database\Seeder;

class ProductRecipeUnitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductRecipeUnit::create([
            'name' => 'gram',
        ]);
    }
}
