<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuRealGilingHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:real-giling-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Real Giling & History';

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
                'title' => "Real Giling & History",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'change_history',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 4,
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
                    'title' => "Roti Manis",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'outdoor_grill',
                    'url' => '/production/grind-history-cookie',
                    'type' => 'item',
                    'order_menu' => 1,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'real-giling-roti-manis.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'real-giling-roti-manis.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'real-giling-roti-manis.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'real-giling-roti-manis.hapus',
                        ]
                    ]
                ],
                [
                    'title' => "Brownies",
                    'parent_id' => $menu->id,
                    'classification' => 'production',
                    'icon' => 'microwave',
                    'url' => '/production/grind-history-brownies',
                    'type' => 'item',
                    'order_menu' => 2,
                    'is_displayed' => 1,
                    'permissions' => [
                        [
                            'name' => 'Lihat',
                            'action' => 'real-giling-brownies.lihat',
                        ],
                        [
                            'name' => 'Tambah',
                            'action' => 'real-giling-brownies.tambah',
                        ],
                        [
                            'name' => 'Ubah',
                            'action' => 'real-giling-brownies.ubah',
                        ],
                        [
                            'name' => 'Hapus',
                            'action' => 'real-giling-brownies.hapus',
                        ]
                    ]
                ]
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
