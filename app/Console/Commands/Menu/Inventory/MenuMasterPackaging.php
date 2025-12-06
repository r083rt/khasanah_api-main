<?php

namespace App\Console\Commands\Menu\Inventory;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuMasterPackaging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:master-packaging';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Master Packaging';

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
        $menu = Menu::where('classification', 'inventory')->where('order_menu', '>', 2)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Master Paketan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'group_work',
                'url' => '/inventory/packaging',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'master-paketan.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'master-paketan.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'master-paketan.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'master-paketan.hapus',
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
