<?php

namespace App\Console\Commands\Menu\Management;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Supplier';

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
                'title' => "Supplier",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'agriculture',
                'url' => '/management/suppliers',
                'type' => 'item',
                'order_menu' => 8,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'supplier.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'supplier.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'supplier.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'supplier.hapus',
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
