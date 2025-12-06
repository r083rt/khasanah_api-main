<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use App\Models\Purchasing\ForecastConversion;
use App\Models\Purchasing\ForecastConversionDetail;

class RegenerateForecastConversion extends Job
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
        $this->onQueue('forecast_conversion');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $product_ingredient_id = $this->data['product_ingredient_id'];
        $forecastConversionIds = ForecastConversion::where([
            'status' => 'new',
            'month' => date('m'),
            'year' => date('Y'),
        ])->pluck('id');

        $forecastConversionDetails = ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastConversionIds)->where('product_ingredient_id', $product_ingredient_id)->get();
        foreach ($forecastConversionDetails as $value) {
            $unit = ProductIngredient::find($product_ingredient_id);

            $getConversion = $this->getConversion($value->conversion, $unit?->product_recipe_unit_id);
            $value->update([
                'conversion_2' => $getConversion['conversion_2'],
                'conversion_unit_id' => $unit?->product_recipe_unit_id,
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
