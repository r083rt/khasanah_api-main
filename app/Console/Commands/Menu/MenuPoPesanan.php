<?php

namespace App\Console\Commands\Menu;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoPesanan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-pesanan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Pesanan';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data = [
            [
                'title' => "Gudang",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => '6_ft_apart',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            $data = [
                [
                    'title' => "Po Pesanan Product",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'kitchen',
                    'url' => '/distribution/products',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-pesanan-produk.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'po-pesanan-produk.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-pesanan-produk.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'po-pesanan-produk.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Po Pesanan Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'bubble_chart',
                    'url' => '/distribution/products',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-pesanan-bahan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'po-pesanan-bahan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-pesanan-bahan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'po-pesanan-bahan.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }
        }
    }
}
