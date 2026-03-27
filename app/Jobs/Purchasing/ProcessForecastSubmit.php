<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Purchasing\Forecast;
use App\Models\Purchasing\ForecastImport;
use App\Models\Purchasing\ForecastConversion as PurchasingForecastConversion;
use Illuminate\Support\Facades\Cache;

class ProcessForecastSubmit extends Job
{
    protected $month;
    protected $additional;

    public function __construct($month, $additional)
    {
        $this->month = $month;
        $this->additional = $additional;
        $this->onQueue('process_forecast_submit');
    }

    public function handle()
    {
        $year = date('Y');

        // ambil branch
        $branchIds = ForecastImport::pluck('branch_id')->unique();

        // delete lama
        Forecast::where([
            'month' => $this->month,
            'year' => $year,
        ])->delete();

        PurchasingForecastConversion::where([
            'month' => $this->month,
            'year' => $year,
            'status' => 'new',
        ])->delete();

        // mapping branch
        $branches = [];
        foreach ($branchIds as $row) {
            $fc = PurchasingForecastConversion::create([
                'month' => $this->month,
                'year' => $year,
                'branch_id' => $row,
                'status_generate' => 'running',
                'additional' => $this->additional
            ]);

            $branches[$row] = $fc->id;
        }

        // insert forecast (chunk + bulk)
        ForecastImport::where('is_valid', 1)
            ->chunk(500, function ($datas) use ($branches, $year) {

                $insertData = [];

                foreach ($datas as $value) {
                    if (!isset($branches[$value->branch_id])) continue;

                    $insertData[] = [
                        'branch_id' => $value->branch_id,
                        'product_id' => $value->product_id,
                        'month' => $value->month,
                        'year' => $year,
                        'sale' => $value->total,
                        'real_sale' => $value->total,
                    ];
                }

                Forecast::insert($insertData);
            });

        // dispatch per branch (Job 2)
        foreach ($branches as $branchId => $conversionId) {
            dispatch(new ForecastConversionBatch(
                $branchId,
                $this->month,
                $conversionId
            ));
        }

        // cleanup
        ForecastImport::whereNotNull('id')->delete();

        Cache::forget('fc_show_' . $this->month);
        Cache::forget('fc_show_detail_' . $this->month);
    }
}
