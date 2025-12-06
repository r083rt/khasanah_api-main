<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'branch_id' => 1,
            'role_id' => 1,
            'name' => 'Admin Kantor Pusat',
            'email' => 'admin@admin.com',
            'phone' => '081903492387',
            'password' => Hash::make('admin123'),
            'address' => 'Bekasi',
            'status' => 'active',
        ]);

        $user->session()->create([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_active_at' => date('Y-m-d H:i:s'),
            'os' => 'MAC'
        ]);

        $user = User::create([
            'branch_id' => 2,
            'role_id' => 1,
            'name' => 'Admin Cabang Agus Salim',
            'email' => 'admin2@admin.com',
            'phone' => '081903492381',
            'password' => Hash::make('admin123'),
            'address' => 'Jakarta',
            'status' => 'active',
        ]);

        $user->session()->create([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_active_at' => date('Y-m-d H:i:s'),
            'os' => 'MAC'
        ]);
    }
}
