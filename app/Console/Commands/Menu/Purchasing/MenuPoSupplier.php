<?php

namespace App\Console\Commands\Menu\Purchasing;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Supplier';

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
                'title' => "PO Supplier",
                'parent_id' => null,
                'classification' => 'purchasing',
                'icon' => 'settings_applications',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 5,
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
                    'title' => "Setting PO",
                    'parent_id' => $menu->id,
                    'classification' => $menu->classification,
                    'icon' => 'settings_applications',
                    'url' => '/purchasing/setting-po',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'setting-po.lihat',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'setting-po.ubah',
                        ],
                    ]
                ],
                [
                    'title' => "PO Supplier",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'checklist',
                    'url' => '/purchasing/po-supplier',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'po-supplier.lihat',
                        ],
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
