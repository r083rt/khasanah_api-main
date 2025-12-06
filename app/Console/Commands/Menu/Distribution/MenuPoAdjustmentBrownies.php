<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoAdjustmentBrownies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-adjustment-brownies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Adjustment Brownies';

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
        $menus = Menu::select('id')->where('classification', 'distribution')->where('title', 'Penyesuaian PO')->first();
        $data = [
            [
                'title' => "Penyesuaian Po Manual",
                'parent_id' => $menus->id,
                'classification' => 'distribution',
                'icon' => 'compare',
                'url' => '/distribution/adjustment-po-manual',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-adjustment-manual.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-adjustment-manual.ubah',
                    ]
                ]
            ],
            [
                'title' => "Penyesuaian Po Brownies",
                'parent_id' => $menus->id,
                'classification' => 'distribution',
                'icon' => 'compare',
                'url' => '/distribution/adjustment-po-brownies',
                'type' => 'item',
                'order_menu' => 4,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-adjustment-bronis.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-adjustment-bronis.ubah',
                    ]
                ]
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
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
