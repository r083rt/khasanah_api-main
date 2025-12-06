<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuBrowniesTargetPlanBufferTarget extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:brownies-buffer-target';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Brownies Buffer Target';

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
        $menu = Menu::where('classification', 'production')->where('title', 'Brownies')->first();

        $data = [
            [
                'title' => "Buffer Target",
                'parent_id' => $menu->id,
                'classification' => 'production',
                'icon' => 'multiline_chart',
                'url' => '/production/brownies-target-buffer',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'produksi-brownies-buffer-target.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'produksi-brownies-buffer-target.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'produksi-brownies-buffer-target.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'produksi-brownies-buffer-target.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->where('parent_id', $menu->id)->first();
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
