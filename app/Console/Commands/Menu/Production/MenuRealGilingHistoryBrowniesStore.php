<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuRealGilingHistoryBrowniesStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:real-giling-history-brownies-store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Real Giling & History Brownies Store';

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
        $menu = Menu::where('classification', 'production')->where('title', 'Real Giling & History')->first();
        $data = [
            [
                'title' => "Real Giling Brownies Toko",
                'parent_id' => $menu->id,
                'classification' => 'production',
                'icon' => 'attractions',
                'url' => '/production/grind-history-brownies-store',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'real-giling-brownies-toko.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'real-giling-brownies-toko.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'real-giling-brownies-toko.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'real-giling-brownies-toko.hapus',
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
