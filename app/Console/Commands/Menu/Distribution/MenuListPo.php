<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuListPo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:list-po';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu List PO';

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
                'title' => "LIST PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'list_alt',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 0,
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
                    'title' => "PO MANUAL",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-manual',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-manual.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-manual.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO PESANAN PRODUK",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-order-product',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-pesanan-produk.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-pesanan-produk.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO PESANAN BAHAN",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-order-ingredient',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-pesanan-bahan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-pesanan-bahan.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "PO BROWNIES",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'list_alt',
                    'url' => '/distribution/list/po-brownies',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'list-po-bronis.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'list-po-bronis.ubah',
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
