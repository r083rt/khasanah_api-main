<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuBrowniesStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:brownies-store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Brownies Store';

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
        $menu = Menu::where('classification', 'production')->where('order_menu', '>', 2)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Brownies Toko",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'class',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 3,
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
                    'title' => "Brownies Toko PO Produksi",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'table_chart',
                    'url' => '/production/brownies-store-po-production',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'brownies-toko-po.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'brownies-toko-po.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'brownies-toko-po.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'brownies-toko-po.hapus',
                        ]
                    ]
                    ],
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
