<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class CustomerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Customer 1',
                'phone' => '081903492372',
                'email' => 'customer1@gmail.com',
                'category' => 'general',
                'address' => 'Cilacap',
                'note' => 'langganan',
            ],
            [
                'name' => 'Customer 2',
                'phone' => '081903492371',
                'email' => 'customer2@gmail.com',
                'category' => 'reseller',
                'address' => 'Bogor',
                'note' => 'ramah',
            ]
        ];

        foreach ($data as $key => $value) {
            $model = Customer::create($value);
            $discount = $model->discounts()->create([
                'product_category_id' => 1,
                'discount_type' => 'nominal',
                'discount' => 1000,
                'created_by' => Auth::id(),
            ]);
            $discount->logs()->create([
                'discount_old' => null,
                'discount_new' => 1000,
                'created_by' => 1,
            ]);
        }
    }
}
