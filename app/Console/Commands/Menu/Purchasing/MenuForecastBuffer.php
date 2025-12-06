<?php

namespace App\Console\Commands\Menu\Purchasing;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuForecastBuffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:forecast-buffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Forecast Buffer';

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
                'title' => "Forecast Buffer",
                'parent_id' => null,
                'classification' => 'purchasing',
                'icon' => 'stacked_line_chart',
                'url' => '/purchasing/buffer',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'forecast-buffer.lihat',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'forecast-buffer.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'forecast-buffer.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', $value['classification'])->where('title', $value['title'])->first();
            if ($menu) {
                $menu->update(Arr::except($value, ['permissions']));

                foreach ($value['permissions'] as $row) {
                    $permission = $menu->permissions()->update($row);
                }
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
