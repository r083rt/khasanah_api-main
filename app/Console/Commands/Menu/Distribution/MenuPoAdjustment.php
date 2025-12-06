<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoAdjustment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-adjustment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Adjustment';

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
                'title' => "Penyesuaian PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'compare',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 4,
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
                    'title' => "Penyesuaian Po Product",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'compare',
                    'url' => '/distribution/adjustment-po-product',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-adjustment-produk.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-adjustment-produk.ubah',
                        ]
                    ]
                ],
                [
                    'title' => "Penyesuaian Po Bahan",
                    'parent_id' => $menu->id,
                    'classification' => 'distribution',
                    'icon' => 'compare',
                    'url' => '/distribution/adjustment-po-ingredients',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-adjustment-bahan.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'po-adjustment-bahan.ubah',
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
