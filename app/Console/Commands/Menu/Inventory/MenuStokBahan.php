<?php

namespace App\Console\Commands\Menu\Inventory;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuStokBahan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:stok-bahan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Stok Bahan';

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
        $menu = Menu::where('classification', 'inventory')->where('order_menu', '>', 5)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Stok Bahan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'aspect_ratio',
                'url' => '/inventory/stock-ingredient',
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'stok-bahan.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'stok-bahan.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'stok-bahan.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'stok-bahan.hapus',
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
