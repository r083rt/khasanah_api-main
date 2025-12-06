<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'BROWNIS',
            'BAHAN BAKU',
            'BINGKISAN',
            'BOLU',
            'BUAYA',
            'CAKE',
            'DESSERT',
            'MAKANAN',
            'MINUMAN',
            'PAKETAN',
            'PASTRY',
            'PERLENGKAPAN',
            'PERMEN',
            'ROTI MANIS',
            'ROTI TAWAR',
            'TAWAR',
            'TITIPAN',
            'TRIAL ROTI',
            'BARANG JADI',
            'MENTAHAN',
        ];

        foreach ($data as $key => $value) {
            ProductCategory::create([
                'name' => $value
            ]);
        }
    }
}
