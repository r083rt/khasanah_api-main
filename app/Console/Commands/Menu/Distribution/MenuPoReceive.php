<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoReceive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-receive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Receive';

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
                'title' => "Terima PO",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'call_received',
                'url' => '/distribution/receive-po',
                'type' => 'item',
                'order_menu' => 3,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-receive.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-receive.ubah',
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
