<?php

namespace Database\Seeders;

use App\Models\ProductIngredient;
use Illuminate\Database\Seeder;

class ProductIngredientTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductIngredient::create([
            'code' => 'CKT1',
            'name' => 'Coklat',
            'hpp' => 10000
        ]);

        ProductIngredient::create([
            'code' => 'PSG1',
            'name' => 'Pisang',
            'hpp' => 500
        ]);
    }
}
