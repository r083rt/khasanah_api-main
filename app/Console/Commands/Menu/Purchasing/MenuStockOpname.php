<?php

namespace App\Console\Commands\Menu\Purchasing;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuStockOpname extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:purchasing-stock-opname';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Purchasing Stock Opname';

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
                'title' => "Stok Opname",
                'parent_id' => null,
                'classification' => 'purchasing',
                'icon' => 'storage',
                'url' => '/purchasing/stock-opname',
                'type' => 'item',
                'order_menu' => 7,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'purchasing-stok-opname.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'purchasing-stok-opname.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'purchasing-stok-opname.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'purchasing-stok-opname.hapus',
                    ],
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
            if ($menu) {
                $menu->update(Arr::except($value, ['permissions']));

                $menu->permissions()->delete();
                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->create($row);
                    $permission->roles()->create(['role_id' => 1]);
                }
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
