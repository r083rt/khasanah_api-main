<?php

namespace App\Console\Commands\Inventory;

use App\Models\Inventory\ProductIngredientStock;
use App\Models\Inventory\ProductIngredientStockDailyLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductIngredientStockDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:product-ingredient-daily-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock product ingredient daily log';

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
            $date =  date('Y-m-d');
            $datas = ProductIngredientStock::get();

            $this->info('Ingredient Stock Log..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                ProductIngredientStockDailyLog::create([
                    'branch_id' => $value->branch_id,
                    'product_ingredient_id' => $value->product_ingredient_id,
                    'stock' => $value->stock,
                    'product_recipe_unit_id' => $value->product_recipe_unit_id,
                    'date' => $date,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at,
                ]);

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
