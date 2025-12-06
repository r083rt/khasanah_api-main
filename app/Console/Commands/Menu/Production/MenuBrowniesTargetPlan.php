<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuBrowniesTargetPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:brownies-target-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Brownies Target Plan';

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
                'title' => "Brownies",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'next_plan',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
            if ($menu) {
                $menu->update(Arr::except($value, ['permissions']));
            } else {
                $menu = Menu::create(Arr::except($value, ['permissions']));
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
            }

            $data = [
                [
                    'title' => "Produksi Harian",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'next_plan',
                    'url' => '/production/brownies-product',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-harian.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-harian.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-harian.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-harian.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Target Penjualan",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'highlight_alt',
                    'url' => '/production/brownies-sale',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-penjualan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-penjualan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-penjualan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-penjualan.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Buffer Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'cast',
                    'url' => '/production/brownies-buffer',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-buffer.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-buffer.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-buffer.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-buffer.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Report PO",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'multiline_chart',
                    'url' => '/production/brownies-report',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-laporan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-laporan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-laporan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-laporan.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "PO Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'table_chart',
                    'url' => '/production/brownies-po-production',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-po.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-po.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-po.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-po.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "PO Gudang",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'add_business',
                    'url' => '/production/brownies-po-warehouse',
                    'type' => 'item',
                    'order_menu' => 5,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'produksi-brownies-gudang.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'produksi-brownies-gudang.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'produksi-brownies-gudang.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'produksi-brownies-gudang.hapus',
                        ]
                    ]
                ],
            ];

            foreach ($data as $value) {
                $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
                if ($menu) {
                    $menu->update(Arr::except($value, ['permissions']));
                } else {
                    $menu = Menu::create(Arr::except($value, ['permissions']));
                    foreach ($value['permissions'] as $row) {
                        $permission = $menu->permissions()->create($row);
                        $permission->roles()->create(['role_id' => 1]);
                    }
                }
            }
        }
    }
}
