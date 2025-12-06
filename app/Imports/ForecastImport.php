<?php

namespace App\Imports;

use App\Jobs\Purchasing\ForecastImport as JobsPurchasingForecastImport;
use App\Models\Purchasing\ForecastImport as PurchasingForecastImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ForecastImport implements ToCollection
{
    protected $month;
    protected $createdBy;

    /**
     * @return void
     */
    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($key > 0) {
                $model = PurchasingForecastImport::create([
                    'branch_name' => $row[0],
                    'product_code' => $row[2],
                    'product_name' => $row[3],
                    'total' => $row[4],
                    'month' => $this->month
                ]);

                dispatch(new JobsPurchasingForecastImport([
                    'id' => $model->id,
                    'branch_name' => $row[0],
                    'product_code' => $row[2],
                    'product_name' => $row[3],
                    'total' => $row[4],
                    'month' => $this->month
                ]));
            }
        }
    }
}
