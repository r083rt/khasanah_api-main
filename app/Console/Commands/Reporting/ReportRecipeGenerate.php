<?php

namespace App\Console\Commands\Reporting;

use App\Models\Inventory\Packaging;
use App\Models\Inventory\PackagingRecipe;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use App\Models\Reporting\ReportRecipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReportRecipeGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporting:generate-recipe {--product_id=} {--master_packaging_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recipe';

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
            $master_packaging_id = $this->option('master_packaging_id');
            if ($master_packaging_id) {
                $productId = ProductRecipe::where('master_packaging_id', $master_packaging_id)->pluck('product_id')->unique();
                if (is_null($productId)) {
                    $datas = Product::select('id', 'name', 'code');

                    if ($productId) {
                        $datas = $datas->where('id', $productId);
                    }

                    $datas = $datas->get();
                } else {
                    $datas = [];
                }
            } else {
                $productId = $this->option('product_id');
                $datas = Product::select('id', 'name', 'code');

                if ($productId) {
                    $datas = $datas->where('id', $productId);
                }

                $datas = $datas->get();
            }

            $this->info('Generate Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                ReportRecipe::where('product_id', $value->id)->delete();

                $recipes = ProductRecipe::select('id', 'product_id', 'product_ingredient_id', 'product_recipe_unit_id', 'measure', 'master_packaging_id')
                    ->where('product_id', $value->id)
                    ->get();

                foreach ($recipes as $row) {
                    if ($row->product_ingredient_id) {
                        $report = ReportRecipe::where('product_id', $value->id)->where('product_ingredient_id', $row->product_ingredient_id)->first();
                        $ingredient = ProductIngredient::find($row->product_ingredient_id);
                        $unit = ProductRecipeUnit::find($ingredient?->product_recipe_unit_id);
                        $brand = ProductIngredientBrand::where('product_ingredient_id', $row->product_ingredient_id)->where('product_recipe_unit_id', $unit?->parent_id_2)->first();
                        $ingredient_code = $brand?->barcode;

                        if ($report) {
                            $report->update([
                                'product_id' => $value->id,
                                'product_name' => $value->name,
                                'product_code' => $value->code,
                                'product_ingredient_id' => $row->product_ingredient_id,
                                'ingredient_name' => $ingredient?->name,
                                'ingredient_code' => $ingredient_code,
                                'qty' => $report->measure + $row->measure,
                                'product_recipe_unit_id' => $row->product_recipe_unit_id,
                                'unit_name' => $unit?->name,
                            ]);
                        } else {
                            ReportRecipe::create([
                                'product_id' => $value->id,
                                'product_name' => $value->name,
                                'product_code' => $value->code,
                                'product_ingredient_id' => $row->product_ingredient_id,
                                'ingredient_name' => $ingredient?->name,
                                'ingredient_code' => $ingredient_code,
                                'qty' => $row->measure,
                                'product_recipe_unit_id' => $row->product_recipe_unit_id,
                                'unit_name' => $unit?->name,
                            ]);
                        }
                    } else {
                        $packaging = Packaging::find($row->master_packaging_id);
                        ReportRecipe::create([
                            'master_packaging_id' => $row->master_packaging_id,
                            'product_id' => $value->id,
                            'product_name' => $value->name,
                            'product_code' => $value->code,
                            'product_ingredient_id' => null,
                            'ingredient_name' => $packaging?->name,
                            'ingredient_code' => null,
                            'qty' => $row->measure,
                            'product_recipe_unit_id' => null,
                            'unit_name' => null,
                        ]);
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $datas = Packaging::select('id', 'name')->get();
            $this->info('Generate Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();
            ReportRecipe::whereNull('product_id')->delete();

            foreach ($datas as $value) {
                $masters = PackagingRecipe::where('master_packaging_id', $value->id)->get();
                foreach ($masters as $master) {
                    if ($master->product_ingredient_id) {
                        $report = ReportRecipe::where('product_ingredient_id', $master->product_ingredient_id)
                            ->where('master_packaging_id', $value->id)
                            ->first();
                        $ingredient = ProductIngredient::find($master->product_ingredient_id);
                        $unit = ProductRecipeUnit::find($ingredient?->product_recipe_unit_id);
                        $brand = ProductIngredientBrand::where('product_ingredient_id', $master->product_ingredient_id)->where('product_recipe_unit_id', $unit?->parent_id_2)->first();
                        $ingredient_code = $brand?->barcode;

                        if ($report) {
                            $report->update([
                                'master_packaging_id' => $value->id,
                                'product_id' => null,
                                'product_name' => $value->name,
                                'product_code' => null,
                                'product_ingredient_id' => $master->product_ingredient_id,
                                'ingredient_name' => $ingredient?->name,
                                'ingredient_code' => $ingredient_code,
                                'qty' =>  $master->measure,
                                'product_recipe_unit_id' => $master->product_ingredient_recipe_unit_id,
                                'unit_name' => $unit?->name,
                            ]);
                        } else {
                            ReportRecipe::create([
                                'master_packaging_id' => $value->id,
                                'product_id' => null,
                                'product_name' => $value->name,
                                'product_code' => null,
                                'product_ingredient_id' => $master->product_ingredient_id,
                                'ingredient_name' => $ingredient?->name,
                                'ingredient_code' => $ingredient_code,
                                'qty' =>  $master->measure,
                                'product_recipe_unit_id' => $master->product_ingredient_recipe_unit_id,
                                'unit_name' => $unit?->name,
                            ]);
                        }
                    }
                }
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
