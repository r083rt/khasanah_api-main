<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'Cash',
            'BCA',
            'Mandiri',
            'BRI',
            'BNI',
            'MokaPay',
            'GoPay',
            'Aku Laku',
            'OVO',
        ];

        foreach ($data as $key => $value) {
            PaymentMethod::create([
                'name' => $value
            ]);
        }
    }
}
