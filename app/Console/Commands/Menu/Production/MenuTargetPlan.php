<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuTargetPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:target-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Target Plan';

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
                'title' => "Rencana Target Roti Manis",
                'parent_id' => null,
                'classification' => 'production',
                'icon' => 'history_edu',
                'url' => '/production/plan',
                'type' => 'item',
                'order_menu' => 1,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'produksi-rencana.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'produksi-rencana.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'produksi-rencana.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'produksi-rencana.hapus',
                    ]
                ]
            ]
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
