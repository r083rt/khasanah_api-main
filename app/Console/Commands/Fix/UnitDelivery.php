<?php

namespace App\Console\Commands\Fix;

use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UnitDelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:unit-delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Unit Delivery';

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

            $this->info('Fixing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $unit = ProductRecipeUnit::find($value->product_recipe_unit_id);
                if ($unit) {
                    $value->update([
                        'product_ingredient_unit_delivery_id' => $unit->parent_id_2
                    ]);
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
