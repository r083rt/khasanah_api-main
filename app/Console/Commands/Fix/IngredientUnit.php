<?php

namespace App\Console\Commands\Fix;

use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IngredientUnit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:ingredient-unit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Ingredient Unit';

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

            $this->info('Processing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                ProductRecipe::where('product_ingredient_id', $value->id)->update([
                    'product_recipe_unit_id' => $value->product_recipe_unit_id
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
