<?php

namespace Database\Seeders;

use App\Models\Management\Area;
use Illuminate\Database\Seeder;

class AreaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Area::create([
            'name' => "Monas",
            'territory_id' => 1
        ]);
    }
}
