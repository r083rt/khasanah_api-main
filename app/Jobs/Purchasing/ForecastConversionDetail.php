<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Purchasing\ForecastConversionDetail as ModelForecastConversionDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ForecastConversionDetail extends Job
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
        $this->onQueue('forecast_conversion_detail');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $conversion = $this->data['conversion'];
        $forecast_conversion_id = $this->data['forecast_conversion_id'];
        $product_ingredient_id = $this->data['product_ingredient_id'];
        $product_recipe_unit_id = $this->data['product_recipe_unit_id'];

        DB::connection('mysql')->transaction(function () use ($conversion, $forecast_conversion_id, $product_ingredient_id, $product_recipe_unit_id) {
            $this->updateData($forecast_conversion_id, $product_ingredient_id, $conversion, $product_recipe_unit_id);
        }, 5);
    }

    /**
     * Update Data
     *
     * @return void
     */
    public function updateData($forecast_conversion_id, $product_ingredient_id, $conversion, $product_recipe_unit_id)
    {
        $cek = ModelForecastConversionDetail::select('id', 'conversion')->where([
            'forecast_conversion_id' => $forecast_conversion_id,
            'product_ingredient_id' => $product_ingredient_id,
        ])->lockForUpdate()->first();
        if ($cek) {
            $conversion = $conversion + $cek->conversion;
            $getConversion = $this->getConversion($conversion, $product_recipe_unit_id);

            $cek->update([
                'conversion' => $conversion,
                'conversion_unit_id' => $product_recipe_unit_id,
                'conversion_2' => $getConversion['conversion_2'],
                'conversion_rounding' => $getConversion['conversion_rounding'],
                'conversion_rounding_unit_id' => $getConversion['conversion_rounding_unit_id'],
                'conversion_latest' => $getConversion['conversion_latest'],
                'conversion_latest_rounding' => $getConversion['conversion_latest_rounding'],
                'conversion_latest_rounding_unit_id' => $getConversion['conversion_latest_rounding_unit_id'],
            ]);
        } else {
            $getConversion = $this->getConversion($conversion, $product_recipe_unit_id);

            ModelForecastConversionDetail::create([
                'forecast_conversion_id' => $forecast_conversion_id,
                'product_ingredient_id' => $product_ingredient_id,
                'conversion' => $conversion,
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

            $data = [
                'conversion_2' => $conversion_2,
                'conversion_rounding' => $forecast_rounding,
                'conversion_rounding_unit_id' => $unitConversionRoundingUnit,
                'conversion_latest' => $conversion_latest,
                'conversion_latest_rounding' => $conversion_latest_rounding,
                'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
            ];

            Cache::put($key, $data, 1800);
            return $data;
        } else {
            return Cache::get($key);
        }
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
