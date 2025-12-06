<?php

namespace Database\Seeders;

use App\Models\Management\Territory;
use Illuminate\Database\Seeder;

class TerritoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Territory::create([
            'name' => "Jakarta",
        ]);
    }
}
