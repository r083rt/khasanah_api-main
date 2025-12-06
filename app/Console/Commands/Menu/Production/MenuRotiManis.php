<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuRotiManis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:roti-manis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Roti Manis';

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
                'title' => "Roti Manis",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'history_edu',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 1,
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
                    'url' => '/production/cookie-product',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'roti-manis-harian.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'roti-manis-harian.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'roti-manis-harian.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'roti-manis-harian.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Target Penjualan",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'highlight_alt',
                    'url' => '/production/cookie-sale',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'roti-manis-penjualan.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'roti-manis-penjualan.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'roti-manis-penjualan.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'roti-manis-penjualan.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Buffer Target",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'multiline_chart',
                    'url' => '/production/cookie-target-buffer',
                    'type' => 'item',
                    'order_menu' => 3,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'roti-manis-target-buffer.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'roti-manis-target-buffer.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'roti-manis-target-buffer.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'roti-manis-target-buffer.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Buffer Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'cast',
                    'url' => '/production/cookie-buffer',
                    'type' => 'item',
                    'order_menu' => 4,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'roti-manis-buffer.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'roti-manis-buffer.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'roti-manis-buffer.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'roti-manis-buffer.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "PO Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'table_chart',
                    'url' => '/production/cookie-po-production',
                    'type' => 'item',
                    'order_menu' => 5,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'roti-manis-po.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'roti-manis-po.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'roti-manis-po.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'roti-manis-po.hapus',
                        ]
                    ]
                ]
            ];

            foreach ($data as $value) {
                $menu = Menu::where('classification', $value['classification'])->where('parent_id', $menu->id)->where('title', $value['title'])->first();
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
