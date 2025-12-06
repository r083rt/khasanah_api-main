<?php

namespace App\Console\Commands\Menu\Pos;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuMasterExpense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:master-expense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Master Expense';

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
                'title' => "Master Biaya",
                'parent_id' => null,
                'classification' => 'pos',
                'icon' => 'money_off',
                'url' => null,
                'type' => 'item',
                'order_menu' => 5,
                'is_displayed' => 1,
                'permissions' => [
                    [
                        'name' => 'Lihat',
                        'action' => 'master-biaya.lihat',
                    ],
                    [
                        'name' => 'Tambah',
                        'action' => 'master-biaya.tambah',
                    ],
                    [
                        'name' => 'Ubah',
                        'action' => 'master-biaya.ubah',
                    ],
                    [
                        'name' => 'Hapus',
                        'action' => 'master-biaya.hapus',
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
