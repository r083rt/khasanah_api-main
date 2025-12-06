<?php

namespace App\Console\Commands\Purchasing;

use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastBuffer;
use App\Models\Purchasing\ForecastConversion;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\ForecastConversionDetailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CekForecastGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:forecast-conversion-generate-cek';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate forecast conversion cek';

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
            $this->info('Running...');

            $datas = ForecastConversion::where('status_generate', 'running');
            $forecast = $datas->count();
            if ($forecast > 0) {
                $job = DB::table('jobs')->where('queue', 'forecast_conversion')->count();
                $job2 = DB::table('jobs')->where('queue', 'forecast_conversion_2')->count();
                $failedJob = DB::table('failed_jobs')->where('queue', 'forecast_conversion')->count();
                $failedJob2 = DB::table('failed_jobs')->where('queue', 'forecast_conversion_2')->count();
                if ($job == 0 && $failedJob == 0 && $job2 == 0 && $failedJob2 == 0) {
                    $forecastConversionIds = $datas->pluck('id');

                    ForecastConversion::where('status_generate', 'running')->update([
                        'status_generate' => 'calculation'
                    ]);
                    foreach ($forecastConversionIds as $forecastConversionId) {
                        $product_ingredient_ids = ForecastConversionDetailLog::select('product_ingredient_id')
                            ->where('forecast_conversion_id', $forecastConversionId)
                            ->pluck('product_ingredient_id')
                            ->unique();
                        foreach ($product_ingredient_ids as $product_ingredient_id) {
                            $conversion = ForecastConversionDetailLog::where('product_ingredient_id', $product_ingredient_id)
                                ->where('forecast_conversion_id', $forecastConversionId)
                                ->sum('conversion');

                            // $conversion_total = $conversion + $buffer;
                            $conversion_total = $conversion;

                            $productIngredient = $this->getProductIngredient($product_ingredient_id);
                            $product_recipe_unit_id = $productIngredient ? $productIngredient->product_recipe_unit_id : null;
                            $getConversion = $this->getConversion($conversion_total, $product_recipe_unit_id);

                            $forecastBuffer = ForecastBuffer::where('product_ingredient_id', $product_ingredient_id)->first();
                            if ($conversion == 0) {
                                $buffer = 0;
                            } else {
                                $buffer = $forecastBuffer ? round($forecastBuffer->buffer / 100 * $getConversion['conversion_latest_rounding']) : 0;
                            }

                            ForecastConversionDetail::create([
                                'forecast_conversion_id' => $forecastConversionId,
                                'product_ingredient_id' => $product_ingredient_id,
                                'conversion' => $conversion,
                                'conversion_total' => $conversion_total,
                                'buffer' => $buffer,
                                'conversion_latest_rounding_total' => $getConversion['conversion_latest_rounding'] + $buffer,
                                'conversion_unit_id' => $product_recipe_unit_id,
                                'conversion_2' => $getConversion['conversion_2'],
                                'conversion_rounding' => $getConversion['conversion_rounding'],
                                'conversion_rounding_unit_id' => $getConversion['conversion_rounding_unit_id'],
                                'conversion_latest' => $getConversion['conversion_latest'],
                                'conversion_latest_rounding' => $getConversion['conversion_latest_rounding'],
                                'conversion_latest_rounding_unit_id' => $getConversion['conversion_latest_rounding_unit_id'],
                            ]);
                        }
                    }

                    ForecastConversion::where('status_generate', 'calculation')->update([
                        'status_generate' => 'done'
                    ]);

                    $this->info('Successfully Done');
                } else {
                    $this->info('Successfully None');
                }
            } else {
                $this->info('Successfully None');
            }


        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }

    /**
     * getProductIngredient
     *
     * @param integer $product_ingredient_id
     * @return Collection
     */
    public function getProductIngredient($product_ingredient_id)
    {
        $key = 'fc_product_ingredient_' . $product_ingredient_id;
        if (!Cache::has($key)) {
            $data = ProductIngredient::select('product_recipe_unit_id')->find($product_ingredient_id);
            Cache::put($key, $data, 1800);
            return $data;
        } else {
            return Cache::get($key);
        }
    }

    /**
     * getConversion
     *
     * @param int $conversion
     * @param int $product_recipe_unit_id
     * @return array
     */
    public function getConversion($conversion, $product_recipe_unit_id)
    {
        $key = 'fc_recipe_' . $product_recipe_unit_id;
        if (!Cache::has($key)) {
            $unit = ProductRecipeUnit::find($product_recipe_unit_id);
            Cache::put($key, $unit, 1800);
        } else {
            $unit = Cache::get($key);
        }

        $unitConversionRoundingUnit = $unit?->parent_id_2;
        $unitConversion2 = $unit?->parent_id_2_conversion;

        if ($unitConversion2) {
            $conversion_2 = $conversion / $unitConversion2;
        } else {
            $conversion_2 = 0;
        }
        $forecast_rounding = forecast_rounding($conversion_2);

        $getLatestConversion = $this->getLatestConversion($conversion, $unit);
        $conversion_latest = $getLatestConversion['conversion_latest'];
        $conversion_latest_rounding = $getLatestConversion['conversion_latest_rounding'];
        $conversion_latest_rounding_unit_id = $getLatestConversion['conversion_latest_rounding_unit_id'];

        return [
            'conversion_2' => $conversion_2,
            'conversion_rounding' => $forecast_rounding,
            'conversion_rounding_unit_id' => $unitConversionRoundingUnit,
            'conversion_latest' => $conversion_latest,
            'conversion_latest_rounding' => $conversion_latest_rounding,
            'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
        ];
    }

    public function getLatestConversion($conversion, $unit)
    {
        if ($unit) {
            if ($unit->parent_id_4 && $unit->parent_id_4 != 0) {
                $conversion_latest_rounding_unit_id = $unit?->parent_id_4;

                //conversion 2
                $conversion_latest = $this->rounding($conversion, $unit?->parent_id_2_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion, $unit?->parent_id_2_conversion)['forecast_rounding'];

                //conversion 3
                $conversion_latest = $this->rounding($conversion_latest_rounding, $unit?->parent_id_3_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion_latest_rounding, $unit?->parent_id_3_conversion)['forecast_rounding'];

                //conversion 4
                $conversion_latest = $this->rounding($conversion_latest_rounding, $unit?->parent_id_4_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion_latest_rounding, $unit?->parent_id_4_conversion)['forecast_rounding'];
            } elseif ($unit->parent_id_3 && $unit->parent_id_3 != 0) {
                $conversion_latest_rounding_unit_id = $unit?->parent_id_3;

                //conversion 2
                $conversion_latest = $this->rounding($conversion, $unit?->parent_id_2_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion, $unit?->parent_id_2_conversion)['forecast_rounding'];

                //conversion 3
                $conversion_latest = $this->rounding($conversion_latest_rounding, $unit?->parent_id_3_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion_latest_rounding, $unit?->parent_id_3_conversion)['forecast_rounding'];
            } elseif ($unit->parent_id_2 && $unit->parent_id_2 != 0) {
                $conversion_latest_rounding_unit_id = $unit?->parent_id_2;

                //conversion 2
                $conversion_latest = $this->rounding($conversion, $unit?->parent_id_2_conversion)['conversion'];
                $conversion_latest_rounding = $this->rounding($conversion, $unit?->parent_id_2_conversion)['forecast_rounding'];
            } else {
                $conversion_latest = null;
                $conversion_latest_rounding = null;
                $conversion_latest_rounding_unit_id = null;
            }
        } else {
            $conversion_latest = null;
            $conversion_latest_rounding = null;
            $conversion_latest_rounding_unit_id = null;
        }

        return [
            'conversion_latest' => $conversion_latest,
            'conversion_latest_rounding' => $conversion_latest_rounding,
            'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
        ];
    }

    public function rounding($conversion, $conversionUnit)
    {
        if ($conversionUnit) {
            $conversion = $conversion / $conversionUnit;
        } else {
            $conversion = 0;
        }

        return [
            'forecast_rounding' => forecast_rounding($conversion),
            'conversion' => $conversion,
        ];
    }
}
