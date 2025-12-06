<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product = Product::create([
            'name' => "BROWNIES KERING",
            'code' => "001",
            'product_category_id' => 1,
            'product_unit_id' => 1,
            'product_unit_delivery_id' => 1,
            'unit_value' => 1,
            'price' => 15000,
            'price_sale' => 10000,
            'gramasi' => 0,
            'mill_barrel' => 0,
            'shop_roller' => 0,
            'note' => null,
        ]);

        $product->first_stocks()->create([
            'branch_id' => 1,
            'product_id' => 1,
            'first_stock' => 10,
        ]);

        $product->first_stocks()->create([
            'branch_id' => 2,
            'product_id' => 1,
            'first_stock' => 5,
        ]);
    }
}
