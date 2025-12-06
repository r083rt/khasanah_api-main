<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TerritoryTableSeeder::class);
        $this->call(AreaTableSeeder::class);
        $this->call(ProductCategoryTableSeeder::class);
        $this->call(BranchTableSeeder::class);
        $this->call(RoleTableSeeder::class);
        $this->call(MenuTableSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(ProductUnitTableSeeder::class);
        // $this->call(ProductTableSeeder::class);
        // $this->call(ProductIngredientTableSeeder::class);
        // $this->call(ProductRecipeUnitTableSeeder::class);
        // $this->call(ProductRecipeTableSeeder::class);
        // $this->call(ProductIncomingTableSeeder::class);
        // $this->call(ProductStockTableSeeder::class);
        // $this->call(ProductAvailableTableSeeder::class);
        $this->call(BranchSettingTableSeeder::class);
        $this->call(PaymentMethodTableSeeder::class);
        $this->call(CustomerTableSeeder::class);
    }
}
