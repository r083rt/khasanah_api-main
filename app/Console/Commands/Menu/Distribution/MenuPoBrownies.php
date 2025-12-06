<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoBrownies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-brownies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Brownies';

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
        $menus = Menu::select('id')->where('classification', 'distribution')->where('title', 'Gudang')->first();

        $data = [
            [
                'title' => "PO BROWNIES",
                'parent_id' => $menus->id,
                'classification' => 'distribution',
                'icon' => 'ac_unit',
                'url' => '/distribution/po-brownies',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-bronis.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'po-bronis.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-bronis.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'po-bronis.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', 'distribution')->where('parent_id', $menus->id)->where('title', $value['title'])->first();
            if ($menu) {
                $menu->update(Arr::except($value, ['permissions']));
            } else {
                $menu = Menu::create(Arr::except($value, ['permissions']));
            }
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }
        }
    }
}
