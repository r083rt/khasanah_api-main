<?php

namespace App\Console\Commands\Ingredient;

use App\Models\Branch;
use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductAvailable;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddRecipe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add recipe';

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
            $ProductIngredient = ProductIngredient::select('id', 'name')->get();
            foreach ($ProductIngredient as $key => $value) {
                $value->update([
                    'name' => trim($value->name)
                ]);
            }

            $data = DB::connection('mysql')->table('resep')->get();

            $this->info('Adding Recipe');
            $bar = $this->output->createProgressBar(count($data));
            $bar->start();

            foreach ($data as $value) {
                $product = Product::select('id')->where('name', strtoupper($value->produk))->first();
                // dd($product);
                if ($product) {
                    // dd($value->bahan);
                    $ingredient = ProductIngredient::where('name', strtoupper($value->bahan))->first();
                    if ($ingredient) {
                        ProductRecipe::create([
                            'product_id' => $product->id,
                            'product_ingredient_id' => $ingredient->id,
                            'product_recipe_unit_id' => $this->getUnitId($value->satuan),
                            'measure' => $value->measure,
                        ]);
                    }
                }
// dd('a');
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

    private function getUnitId($unit)
    {
        switch (strtolower($unit)) {
            case 'gram':
                return 1;
                break;

            case 'pieces':
                return 2;
                break;

            default:
                return null;
                break;
        }
    }
}
