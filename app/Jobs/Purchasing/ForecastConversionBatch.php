<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Purchasing\Forecast;
use App\Models\ProductRecipe;
use App\Models\Inventory\Packaging;
use App\Models\Purchasing\ForecastConversionDetailLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForecastConversionBatch extends Job
{
    protected $branchId;
    protected $month;
    protected $conversionId;

    public function __construct($branchId, $month, $conversionId)
    {
        $this->branchId = $branchId;
        $this->month = $month;
        $this->conversionId = $conversionId;
        $this->onQueue('forecast_conversion_batch');
    }

    public function handle()
    {
        $year = date('Y');

        $datas = Forecast::where('branch_id', $this->branchId)
            ->where('month', $this->month)
            ->where('year', $year)
            ->get();

        if ($datas->isEmpty()) return;

        $productIds = $datas->pluck('product_id')->unique();

        $recipes = ProductRecipe::whereIn('product_id', $productIds)->get();
        $recipesGrouped = $recipes->groupBy('product_id');

        $packagingIds = $recipes->pluck('master_packaging_id')->filter()->unique();
        $packagings = Packaging::whereIn('id', $packagingIds)->get()->keyBy('id');

        $logs = [];

        foreach ($datas as $data) {

            $productRecipes = $recipesGrouped[$data->product_id] ?? collect();
            $qty = $data->sale;

            foreach ($productRecipes as $value) {

                // PACKAGING
                if ($value->master_packaging_id) {

                    $packaging = $packagings[$value->master_packaging_id] ?? null;
                    if (!$packaging) continue;

                    $totalMeasureXqty = $value->measure * $qty;
                    $paketan = $packaging->gramasi_production != 0
                        ? round($totalMeasureXqty / $packaging->gramasi_production)
                        : 0;

                    foreach ($packaging->recipes as $row) {

                        if ($row->product_ingredient_id) {

                            $logs[] = $this->buildLog(
                                $data,
                                $row->product_ingredient_id,
                                $qty,
                                $value->measure,
                                $paketan * $row->measure,
                                $value->master_packaging_id,
                                $totalMeasureXqty,
                                $packaging->gramasi_production,
                                $paketan,
                                $row->measure
                            );
                        }
                    }
                }

                // DIRECT INGREDIENT
                if ($value->product_ingredient_id) {

                    $logs[] = $this->buildLog(
                        $data,
                        $value->product_ingredient_id,
                        $qty,
                        $value->measure,
                        $qty * $value->measure
                    );
                }
            }

            if (count($logs) >= 1000) {
                ForecastConversionDetailLog::insert($logs);
                $logs = [];
            }
        }

        if (!empty($logs)) {
            ForecastConversionDetailLog::insert($logs);
        }
    }

    private function buildLog(
        $data,
        $ingredientId,
        $qty,
        $measure,
        $conversion,
        $masterPackagingId = null,
        $qtyMeasure = null,
        $gramasiProduction = null,
        $qtyPackaging = null,
        $measurePackaging = null
    ) {
        return [
            'forecast_conversion_id' => $this->conversionId,
            'product_id' => $data->product_id,
            'product_ingredient_id' => $ingredientId,
            'master_packaging_id' => $masterPackagingId,
            'qty' => $qty,
            'measure' => $measure,
            'qty_measure' => $qtyMeasure,
            'gramasi_production' => $gramasiProduction,
            'qty_packaging' => $qtyPackaging,
            'measure_packaging' => $measurePackaging,
            'conversion' => $conversion,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function failed(Throwable $exception)
    {
        Log::info('forecast_conversion_batch: ' . $exception);
    }
}
