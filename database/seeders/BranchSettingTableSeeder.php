<?php

namespace Database\Seeders;

use App\Models\Management\BranchSetting;
use Illuminate\Database\Seeder;

class BranchSettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BranchSetting::create([
            'branch_id' => 1,
            'mac' => "f0:18:98:56:b6:72",
        ]);
    }
}
