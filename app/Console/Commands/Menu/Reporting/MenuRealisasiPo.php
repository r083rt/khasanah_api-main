<?php

namespace App\Console\Commands\Menu\Reporting;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuRealisasiPo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:realisasi-po';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Reaslisasi Po Surat Jalan';

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
                'title' => "Realisasi PO Surat Jalan",
                'parent_id' => null,
                'classification' => 'central_report',
                'icon' => 'ac_unit',
                'url' => '/reporting/po-travel-doc',
                'type' => 'item',
                'order_menu' => 12,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-surat-jalan.lihat',
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
