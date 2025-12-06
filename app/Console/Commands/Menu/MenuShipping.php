<?php

namespace App\Console\Commands\Menu;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuShipping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:shipping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Shipping';

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
                'title' => "Jalur Pengiriman",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'linear_scale',
                'url' => '/management/shippings',
                'type' => 'item',
                'order_menu' => 7,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'pengiriman.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'pengiriman.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'pengiriman.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'pengiriman.hapus',
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
