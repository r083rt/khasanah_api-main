<?php

namespace Database\Seeders;

use App\Models\ProductStock;
use Illuminate\Database\Seeder;

class ProductStockTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductStock::create([
            'branch_id' => 1,
            'product_id' => 1,
            'stock' => 10,
        ]);

        ProductStock::create([
            'branch_id' => 2,
            'product_id' => 1,
            'stock' => 5,
        ]);
    }
}
