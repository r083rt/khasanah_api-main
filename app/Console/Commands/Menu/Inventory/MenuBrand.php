<?php

namespace App\Console\Commands\Menu\Inventory;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuBrand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:brand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Brand';

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
        $menu = Menu::where('classification', 'inventory')->where('order_menu', '>', 3)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Master Kategori Bahan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'ac_unit',
                'url' => '/inventory/brand',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'merk.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'merk.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'merk.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'merk.hapus',
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
