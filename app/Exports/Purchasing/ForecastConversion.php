<?php

namespace App\Exports\Purchasing;

use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastConversion as PurchasingForecastConversion;
use App\Models\Purchasing\ForecastConversionDetail;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class ForecastConversion implements FromArray, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $month;
    protected $branchId;

    /**
     * @return void
     */
    public function __construct($month, $branchId)
    {
        $this->month = $month;
        $this->branchId = $branchId;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'KONVERSI FORECAST';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'ID Bahan',
            'Bahan',
            'Konversi',
            'Konversi Total',
            'Satuan',
            'Unit 2 Konversi',
            'Unit 2 Pembulatan',
            'Unit 2 Satuan',
            'Unit Terbesar Konversi',
            'Unit Terbesar Pembulatan',
            // 'Unit Terbesar Buffer',
            // 'Unit Terbesar Total',
            'Unit Terbesar Satuan',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        $brand = ProductIngredientBrand::select('barcode')->where('product_ingredient_id', $data['product_ingredient_id'])->where('product_recipe_unit_id', $data['conversion_rounding_unit_id'])->first();

        return [
            $brand?->barcode,
            $data['product_ingredient'] ? $data['product_ingredient']['name'] : null,
            $data['conversion'],
            $data['conversion_total'],
            $data['conversion_unit'] ? $data['conversion_unit']['name'] : null,
            $data['conversion_2'],
            $data['conversion_rounding'],
            $data['conversion_rounding_unit'] ? $data['conversion_rounding_unit']['name'] : null,
            $data['conversion_latest'],
            $data['conversion_latest_rounding'],
            // $data['buffer'],
            // $data['conversion_latest_rounding_total'],
            $data['conversion_latest_rounding_unit'] ? $data['conversion_latest_rounding_unit']['name'] : null,
        ];
    }

    /**
    *
    * @return array
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

   /**
     * Get data to be export
     *
     * @return Collection
     */
    public function array(): array
    {
        if ($this->branchId) {
            $data = PurchasingForecastConversion::select('id', 'month', 'year', 'status', 'branch_id')
                ->with(['forecastConversionDetails', 'branch:id,name', 'forecastConversionDetails.conversionUnit:id,name', 'forecastConversionDetails.conversionRoundingUnit:id,name', 'forecastConversionDetails.productIngredient:id,name', 'forecastConversionDetails.conversionLatestRoundingUnit:id,name',])
                ->where('month', $this->month)
                ->where('year', date('Y'))
                ->where('branch_id', $this->branchId)
                ->first();

            if ($data) {
                return $data->forecastConversionDetails->sortBy('productIngredient.name')->values()->toArray();
            }
        } else {
            $key = 'fc_show_detail_' . $this->month;
            if (!Cache::has($key)) {
                $forecastIds = PurchasingForecastConversion::where('month', $this->month)->where('year', date('Y'))->pluck('id');
                $product_ingredient_ids = ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastIds)->whereNotNull('product_ingredient_id')->groupBy('product_ingredient_id')->pluck('product_ingredient_id');

                $productIngredients = ProductIngredient::select('id', 'name', 'product_recipe_unit_id')->whereIn('id', $product_ingredient_ids)->orderBy('name')->get();
                $forecast_conversion_details = [];
                foreach ($productIngredients as $value) {
                    $detail = ForecastConversionDetail::select('id', 'conversion_unit_id', 'conversion_rounding_unit_id', 'conversion_latest_rounding', 'conversion_latest_rounding_unit_id')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->first();
                    $conversion_unit_id = $detail?->conversion_unit_id;
                    $conversion_unit = $detail?->conversionUnit;
                    $conversion_rounding_unit_id = $detail?->conversion_rounding_unit_id;
                    $conversion_rounding_unit = $detail?->conversionRoundingUnit;
                    $conversion_latest_rounding_unit_id = $detail?->conversion_latest_rounding_unit_id;
                    $conversion_latest_rounding_unit = $detail?->conversionLatestRoundingUnit;

                    $conversion_2 = ForecastConversionDetail::select('conversion_2')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_2');
                    $conversion = ForecastConversionDetail::select('conversion')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion');
                    $conversion_total = ForecastConversionDetail::select('conversion_total')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_total');
                    $buffer = ForecastConversionDetail::select('buffer')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('buffer');

                    $unit = ProductRecipeUnit::where('id', $value->product_recipe_unit_id)->first();
                    $pembagi = 1;
                    if ($unit) {
                        if ($unit->parent_id_4) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion * $unit->parent_id_4_conversion;
                        } elseif ($unit->parent_id_3) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion;
                        } elseif ($unit->parent_id_2) {
                            $pembagi = $unit->parent_id_2_conversion;
                        } else{
                            $pembagi = 1;
                        }
                    }

                    $conversion_latest_rounding = round(forecast_rounding($conversion_total / $pembagi), 2);
                    $forecast_conversion_details[] = [
                        'id' => 0,
                        'forecast_conversion_id' => 0,
                        'product_ingredient_id' => $value->id,
                        'product_ingredient' => [
                            'id' => $value->id,
                            'name' => $value->name,
                        ],
                        'conversion' => $conversion,
                        'conversion_total' => $conversion_total,
                        'buffer' => $buffer,
                        'conversion_2' => round($conversion_2, 2),
                        'conversion_unit_id' => $conversion_unit_id,
                        'conversion_unit' => $conversion_unit,
                        'conversion_rounding' => round(forecast_rounding($conversion_2), 2),
                        'conversion_rounding_unit_id' => $conversion_rounding_unit_id,
                        'conversion_rounding_unit' => $conversion_rounding_unit,
                        'conversion_latest' => round($conversion_total / $pembagi, 2),
                        'conversion_latest_rounding' => $conversion_latest_rounding,
                        'conversion_latest_rounding_total' => $conversion_latest_rounding + $buffer,
                        'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
                        'conversion_latest_rounding_unit' => $conversion_latest_rounding_unit,
                    ];
                }

                Cache::put($key, $forecast_conversion_details, 86400);
            } else {
                $forecast_conversion_details = Cache::get($key);
            }

            return $forecast_conversion_details;
        }
    }
}
