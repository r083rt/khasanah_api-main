<?php

namespace Database\Seeders;

use App\Models\ProductAvailable;
use Illuminate\Database\Seeder;

class ProductAvailableTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductAvailable::create([
            'branch_id' => 1,
            'product_id' => 1,
        ]);

        ProductAvailable::create([
            'branch_id' => 2,
            'product_id' => 1,
        ]);
    }
}
