<?php

namespace App\Console\Commands\Menu\Inventory;

use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MenuMasterBahan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:master-bahan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Menu Master Bahan';

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
        $menu = Menu::where('classification', 'inventory')->where('order_menu', '>', 1)->whereNull('parent_id')->get();
        foreach ($menu as $value) {
            $value->update([
                'order_menu' => $value->order_menu + 1
            ]);
        }

        $data = [
            [
                'title' => "Master Merk Bahan",
                'parent_id' => null,
                'classification' => 'inventory',
                'icon' => 'bubble_chart',
                'url' => null,
                'type' => 'collapse',
                'order_menu' => 2,
                'is_displayed' => 1,
                'permissions' => []
            ],
        ];

        foreach ($data as $value) {
            $menu = Menu::create(Arr::except($value, ['permissions']));
            foreach ($value['permissions'] as $row) {
                $permission = $menu->permissions()->create($row);
                $permission->roles()->create(['role_id' => 1]);
            }

            Menu::where('title', 'Master Bahan')->where('classification', 'inventory')->whereNotNull('parent_id')->update([
                'parent_id' => $menu->id
            ]);

            Menu::where('title', 'Satuan Bahan')->where('classification', 'inventory')->update([
                'parent_id' => $menu->id
            ]);

            Menu::where('title', 'Resep')->where('classification', 'inventory')->update([
                'parent_id' => $menu->id
            ]);
        }
    }
}
