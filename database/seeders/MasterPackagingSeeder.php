<?php

namespace Database\Seeders;

use App\Models\Inventory\Packaging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class MasterPackagingSeeder extends Seeder
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
                'name' => 'PAKET BOLU GULUNG',
                'grinds' => 6,
                'gramasi' => 3700,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 38
                    ],
                    [
                        'product_id' => 16
                    ],
                    [
                        'product_id' => 15
                    ]
                ]
            ],
            [
                'name' => 'PAKET BOLU BULAT',
                'grinds' => 6,
                'gramasi' => 3000,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 281
                    ],
                    [
                        'product_id' => 8
                    ],
                    [
                        'product_id' => 9
                    ],
                    [
                        'product_id' => 290
                    ]
                ]
            ],
            [
                'name' => 'PAKET BAKAR',
                'grinds' => 12,
                'gramasi' => 6460,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 271
                    ],
                    [
                        'product_id' => 269
                    ],
                    [
                        'product_id' => 293
                    ],
                ]
            ],
            [
                'name' => 'BROWNIES KUKUS',
                'grinds' => 11,
                'gramasi' => 5590,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 270
                    ],
                ]
            ],
            [
                'name' => 'BOLU TALAS',
                'grinds' => 11,
                'gramasi' => 4650,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 272
                    ],
                ]
            ],
            [
                'name' => 'LAPIS TALAS BOGOR',
                'grinds' => 9,
                'gramasi' => 5220,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 284
                    ],
                ]
            ],
            [
                'name' => 'CAKE PISANG',
                'grinds' => 6,
                'gramasi' => 2820,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 280
                    ],
                ]
            ],
            [
                'name' => 'CHEESE CAKE',
                'grinds' => 6,
                'gramasi' => 2540,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 274
                    ],
                ]
            ],
            [
                'name' => 'KETAN HITAM',
                'grinds' => 10,
                'gramasi' => 4425,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 273
                    ],
                ]
            ],
            [
                'name' => 'BOLU SUSU',
                'grinds' => 11,
                'gramasi' => 4180,
                'unit' => 'Gram',
                'products' => [
                    [
                        'product_id' => 51
                    ],
                ]
            ],
        ];

        foreach ($data as $value) {
            $model = Packaging::create(Arr::except($value, 'products'));
            $model->products()->attach($value['products']);
        }
    }
}
