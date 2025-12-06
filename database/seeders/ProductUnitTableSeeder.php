<?php

namespace Database\Seeders;

use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductUnitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'Dus',
            'Ikat',
            'Jerigen',
            'Karung',
            'Pail',
            'Pak',
            'Paket',
            'Pcs',
            'Peti',
            'Sak',
            'Toples',
        ];

        foreach ($data as $key => $value) {
            ProductUnit::create([
                'name' => $value
            ]);
        }
    }
}
