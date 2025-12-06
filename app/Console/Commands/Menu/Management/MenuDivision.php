<?php

namespace App\Console\Commands\Menu\Management;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuDivision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:division';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Division';

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
                'title' => "Divisi & Sub Divisi",
                'parent_id' => null,
                'classification' => 'management',
                'icon' => 'account_tree',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 9,
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
                    'title' => "Divisi",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'account_tree',
                    'url' => '/management/division',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'divisi.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'divisi.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'divisi.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'divisi.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Sub Divisi",
                    'parent_id' => $menu->id,
                    'classification' => 'management',
                    'icon' => 'account_tree',
                    'url' => '/management/sub-division',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'sub-divisi.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'sub-divisi.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'sub-divisi.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'sub-divisi.hapus',
                        ]
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
