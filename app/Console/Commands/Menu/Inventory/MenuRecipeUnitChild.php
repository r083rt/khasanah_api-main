<?php

namespace App\Console\Commands\Menu\Inventory;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuRecipeUnitChild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:satuan-child-bahan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Satuan Child Bahan';

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
        $menu = Menu::where('classification', 'inventory')->where('order_menu', '>', 2)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Satuan Bahan Child",
                'parent_id' => 81,
                'classification' => 'inventory',
                'icon' => 'attachment',
                'url' => '/inventory/recipe-units-child',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'resep-unit-child.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'resep-unit-child.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'resep-unit-child.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'resep-unit-child.hapus',
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
