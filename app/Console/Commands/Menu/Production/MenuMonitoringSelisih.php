<?php

namespace App\Console\Commands\Menu\Production;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuMonitoringSelisih extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:monitoring-closing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Monitoring Selisih Closing';

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
                'title' => "Monitoring Selisih Closing",
                'parent_id' => null,
                'classification' => 'central_report',
                'icon' => 'live_tv',
                'url' => '/reporting/monitoring-closing',
                'type' => 'item',
                'order_menu' => 6,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'monitor-closing.lihat',
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
