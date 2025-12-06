<?php

namespace Database\Seeders;

use App\Models\ProductIncoming;
use Illuminate\Database\Seeder;

class ProductIncomingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductIncoming::create([
            'branch_id' => 1,
            'product_id' => 1,
            'total' => 10,
            'price' => 15000,
            'total_price' => 150000,
            'created_by' => 1,
        ]);

        ProductIncoming::create([
            'branch_id' => 2,
            'product_id' => 1,
            'total' => 5,
            'price' => 15000,
            'total_price' => 75000,
            'created_by' => 2,
        ]);
    }
}
