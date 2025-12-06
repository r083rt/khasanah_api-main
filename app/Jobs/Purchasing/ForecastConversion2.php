<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Jobs\Purchasing\ForecastConversion as PurchasingForecastConversion;

class ForecastConversion2 extends Job
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
        $this->onQueue('forecast_conversion_2');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $forecast = $this->data['forecast'];
        $forecast_conversion_id = $this->data['forecast_conversion_id'];
        $productRecipes = $this->data['product_recipes'];

        foreach ($forecast as $row) {
            $recipes = $productRecipes->where('product_id', $row->product_id);
            if ($recipes) {
                $data = [
                    'product_id' => $row->product_id,
                    'sale' => $row->sale,
                    'forecast_conversion_id' => $forecast_conversion_id,
                    'recipes' => $recipes
                ];

                dispatch(new PurchasingForecastConversion($data));
            }
        }
    }
}
