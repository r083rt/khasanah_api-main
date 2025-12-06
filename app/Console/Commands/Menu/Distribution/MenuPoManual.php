<?php

namespace App\Console\Commands\Menu\Distribution;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuPoManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:po-manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Po Manual';

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
        $menus = Menu::where('classification', 'distribution')->whereNull('parent_id')->where('title', '!=', 'PO Manual')->orderBy('order_menu')->get();
        foreach ($menus as $value) {
            if ($value->order_menu > 1) {
                $value->update([
                    'order_menu' => ($value->order_menu + 1)
                ]);
            }
        }

        $data = [
            [
                'title' => "PO Manual",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'rate_review',
                'url' => '/distribution/po-manuals',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-manual.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'po-manual.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-manual.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'po-manual.hapus',
                    ]
                ]
            ]
        ];

        foreach ($data as $value) {
            $menu = Menu::where('classification', 'distribution')->where('title', $value['title'])->first();
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
