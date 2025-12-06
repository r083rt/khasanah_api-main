<?php

namespace App\Console\Commands\Menu\Purchasing;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuMasterSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:purchasing-supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Purchasing Supplier';

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
                'title' => "Master Supplier",
                'parent_id' => null,
                'classification' => 'purchasing',
                'icon' => 'toys',
                'url' => '/purchasing/supplier',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'purchasing-supplier.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'purchasing-supplier.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'purchasing-supplier.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'purchasing-supplier.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
            if ($menu) {
                $menu->update(Arr::except($value, ['permissions']));

                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->update($row);
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
