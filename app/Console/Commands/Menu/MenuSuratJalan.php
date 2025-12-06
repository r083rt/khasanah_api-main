<?php

namespace App\Console\Commands\Menu;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuSuratJalan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:surat-jalan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Surat Jalan';

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
                'title' => "Cetak Surat Jalan",
                'parent_id' => null,
                'classification' => 'distribution',
                'icon' => 'print',
                'url' => '/distribution/print-sj',
                'type' => 'item',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'po-print-sj.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'po-print-sj.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'po-print-sj.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'po-print-sj.hapus',
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
