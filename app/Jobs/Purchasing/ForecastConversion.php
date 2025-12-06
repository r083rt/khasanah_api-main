<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\ForecastConversionDetailLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForecastConversion extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        // $this->onConnection('redis_queue_forecast_conversion');
        $this->onQueue('forecast_conversion');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::connection('mysql')->transaction(function () {
            $this->execution();
        });
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function execution()
    {
        $product_id = $this->data['product_id'];
        $recipes = $this->data['recipes'];
        $qty = $this->data['sale'];
        $forecast_conversion_id = $this->data['forecast_conversion_id'];

        // $keyRecipe = 'fc_packaging_' . $product_id;
        // if (!Cache::has($keyRecipe)) {
        //     $recipes = ProductRecipe::where([
        //         'product_id' => $product_id,
        //     ])->get();
        //     Cache::put($keyRecipe, $recipes, 1800);
        // } else {
        //     $recipes = Cache::get($keyRecipe);
        // }

        // $recipes = ProductRecipe::where([
        //     'product_id' => $product_id,
        // ])->get();
        // DB::connection('mysql')->transaction(function () use ($forecast_conversion_id, $recipes, $qty, $product_id) {
            foreach ($recipes as $value) {
                if ($value->master_packaging_id) {
                    //pake job lain
                    // $keyPackaging = 'fc_packaging_' . $value->master_packaging_id;
                    // if (!Cache::has($keyPackaging)) {
                    //     $packaging = Packaging::find($value->master_packaging_id);
                    //     Cache::put($keyPackaging, $packaging, 1800);
                    // } else {
                    //     $packaging = Cache::get($keyPackaging);
                    // }
                    $packaging = Packaging::find($value->master_packaging_id);

                    if ($packaging) {
                        $paketan = 0;
                        $totalMeasureXqty = $value->measure * $qty;
                        if ($packaging->gramasi_production != 0) {
                            $paketan = round($totalMeasureXqty / $packaging->gramasi_production);
                        }

                        foreach ($packaging->recipes as $row) {
                            if ($row->product_ingredient_id) {
                                // $keyIngredient = 'fc_ingredient_' . $row->product_ingredient_id;
                                // if (!Cache::has($keyIngredient)) {
                                //     $productIngredient = ProductIngredient::find($row->product_ingredient_id);
                                //     Cache::put($keyIngredient, $packaging, 1800);
                                // } else {
                                //     $productIngredient = Cache::get($keyIngredient);
                                // }
                                // $productIngredient = ProductIngredient::find($row->product_ingredient_id);

                                $conversion = $paketan * $row->measure;
                                $this->insertData($forecast_conversion_id, $product_id, $row->product_ingredient_id, $qty, $value->measure, $conversion, $value->master_packaging_id, $totalMeasureXqty, $packaging->gramasi_production, $paketan, $row->measure);
                            }

                            //packaging dalam packaging 1
                            if ($row->master_packaging_recipe_id) {
                                $packaging2 = Packaging::find($row->master_packaging_id);

                                if ($packaging2) {
                                    $paketan2 = 0;
                                    $totalMeasureXqty2 = $row->measure * $qty;
                                    if ($packaging2->gramasi_production != 0) {
                                        $paketan2 = round($totalMeasureXqty2 / $packaging2->gramasi_production);
                                    }

                                    foreach ($packaging2->recipes as $recipe) {
                                        if ($recipe->product_ingredient_id) {

                                            $conversion2 = $paketan2 * $recipe->measure;
                                            $this->insertData(
                                                $forecast_conversion_id,
                                                $product_id,
                                                $recipe->product_ingredient_id,
                                                $qty,
                                                $value->measure,
                                                $conversion2,
                                                $value->master_packaging_id,
                                                $totalMeasureXqty,
                                                $packaging->gramasi_production,
                                                $paketan,
                                                $recipe->measure
                                            );
                                        }

                                        //packaging dalam packaging 2
                                        if ($recipe->master_packaging_recipe_id) {
                                            $packaging3 = Packaging::find($recipe->master_packaging_id);

                                            if ($packaging3) {
                                                $paketan3 = 0;
                                                $totalMeasureXqty3 = $recipe->measure * $qty;
                                                if ($packaging3->gramasi_production != 0) {
                                                    $paketan3 = round($totalMeasureXqty3 / $packaging3->gramasi_production);
                                                }

                                                foreach ($packaging3->recipes as $recipe3) {
                                                    if ($recipe3->product_ingredient_id) {

                                                        $conversion3 = $paketan3 * $recipe3->measure;
                                                        $this->insertData(
                                                            $forecast_conversion_id,
                                                            $product_id,
                                                            $recipe3->product_ingredient_id,
                                                            $qty,
                                                            $value->measure,
                                                            $conversion3,
                                                            $value->master_packaging_id,
                                                            $totalMeasureXqty,
                                                            $packaging->gramasi_production,
                                                            $paketan,
                                                            $recipe3->measure
                                                        );
                                                    }

                                                    //packaging dalam packaging 3
                                                    if ($recipe3->master_packaging_recipe_id) {
                                                        $packaging4 = Packaging::find($recipe->master_packaging_id);

                                                        if ($packaging4) {
                                                            $paketan4 = 0;
                                                            $totalMeasureXqty4 = $recipe->measure * $qty;
                                                            if ($packaging4->gramasi_production != 0) {
                                                                $paketan4 = round($totalMeasureXqty4 / $packaging4->gramasi_production);
                                                            }

                                                            foreach ($packaging4->recipes as $recipe4) {
                                                                if ($recipe4->product_ingredient_id) {

                                                                    $conversion4 = $paketan4 * $recipe4->measure;
                                                                    $this->insertData(
                                                                        $forecast_conversion_id,
                                                                        $product_id,
                                                                        $recipe4->product_ingredient_id,
                                                                        $qty,
                                                                        $value->measure,
                                                                        $conversion4,
                                                                        $value->master_packaging_id,
                                                                        $totalMeasureXqty,
                                                                        $packaging->gramasi_production,
                                                                        $paketan,
                                                                        $recipe4->measure
                                                                    );
                                                                }

                                                                //packaging dalam packaging 4
                                                                if ($recipe4->master_packaging_recipe_id) {
                                                                    //done
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else if ($value->product_ingredient_id) {
                    $conversion =  $qty * $value->measure;

                    // dispatch(new PurchasingForecastConversionDetail([
                    //     'conversion' => $conversion,
                    //     'forecast_conversion_id' => $forecast_conversion_id,
                    //     'product_ingredient_id' => $value->product_ingredient_id,
                    //     'product_recipe_unit_id' => $value->product_recipe_unit_id,
                    // ]));
                    // $this->updateData($forecast_conversion_id, $value->product_ingredient_id, $conversion, $value->product_recipe_unit_id);
                    $this->insertData($forecast_conversion_id, $product_id, $value->product_ingredient_id, $qty, $value->measure, $conversion);
                }
            }
        // });
    }

    /**
     * Insert Data
     *
     * @return void
     */
    public function insertData($forecast_conversion_id, $product_id, $product_ingredient_id, $qty, $measure, $conversion, $master_packaging_id = null, $qty_measure = null, $gramasi_production = null, $qty_packaging = null, $measure_packaging = null)
    {
        ForecastConversionDetailLog::create([
            'forecast_conversion_id' => $forecast_conversion_id,
            'product_id' => $product_id,
            'product_ingredient_id' => $product_ingredient_id,
            'master_packaging_id' => $master_packaging_id,
            'qty' => $qty,
            'measure' => $measure,
            'qty_measure' => $qty_measure,
            'gramasi_production' => $gramasi_production,
            'qty_packaging' => $qty_packaging,
            'measure_packaging' => $measure_packaging,
            'conversion' => $conversion,
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info('forecast_conversion: ' . $exception);
    }
}
