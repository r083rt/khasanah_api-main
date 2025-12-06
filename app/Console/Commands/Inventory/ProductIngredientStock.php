<?php

namespace App\Console\Commands\Inventory;

use App\Models\Branch;
use App\Models\Inventory\ProductIngredientStock as ModelProductIngredientStock;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductIngredientStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:product-ingredient';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock product ingredient';

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
        try {
            $datas = ProductIngredient::select('id', 'product_recipe_unit_id')->get();
            $branches = Branch::select('id')->get();

            $this->info('Ingredient Stock Log..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $product_recipe_unit_id = $value->product_recipe_unit_id;
                $productRecipeUnit = ProductRecipeUnit::find($product_recipe_unit_id);
                if ($productRecipeUnit) {
                    $unit1 = $product_recipe_unit_id;
                    $unit2 = $productRecipeUnit->parent_id_2;
                    $unit3 = $productRecipeUnit->parent_id_3;
                    $unit4 = $productRecipeUnit->parent_id_4;

                    if ($unit1) {
                        foreach ($branches as $branch) {
                            ModelProductIngredientStock::updateOrCreate(
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit1,
                                ],
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit1,
                                    'stock' => 0,
                                ]
                            );
                        }
                    }

                    if ($unit2) {
                        foreach ($branches as $branch) {
                            ModelProductIngredientStock::updateOrCreate(
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit2,
                                ],
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit2,
                                    'stock' => 0,
                                ]
                            );
                        }
                    }

                    if ($unit3) {
                        foreach ($branches as $branch) {
                            ModelProductIngredientStock::updateOrCreate(
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit3,
                                ],
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->id,
                                    'product_recipe_unit_id' => $unit3,
                                    'stock' => 0,
                                ]
                            );
                        }
                    }

                    if ($unit4) {
                        foreach ($branches as $branch) {
                            ModelProductIngredientStock::updateOrCreate(
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->product_recipe_unit_id,
                                    'product_recipe_unit_id' => $unit4,
                                ],
                                [
                                    'branch_id' => $branch->id,
                                    'product_ingredient_id' => $value->product_recipe_unit_id,
                                    'product_recipe_unit_id' => $unit4,
                                    'stock' => 0,
                                ]
                            );
                        }
                    }
                }


                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
