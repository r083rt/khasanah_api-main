<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductStockAdjustment as InventoryProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\Prefix;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

use App\Models\Purchasing\ForecastConversionApproval;

class SettingPoQtyExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $id;

    /**
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Setting PO';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Item',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '10',
            '11',
            '12',
            '13',
            '14',
            '15',
            '16',
            '17',
            '18',
            '19',
            '20',
            '21',
            '22',
            '23',
            '24',
            '25',
            '26',
            '27',
            '28',
            '29',
            '30',
            '31'
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
        $results = [];
        $data = ForecastConversionApproval::with([
            'branch:id,name',
            'forecastConversionSettingPo',
            'forecastConversionSettingPo.productRecipeUnit:id,name',
            'forecastConversionSettingPo.productIngredient:id,name,brand_id',
            'forecastConversionSettingPo.brand:id,name',
            'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers',
            'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers.forecastConversionSettingPoSupplierQtyDeliveries',
            'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers.purchasingSupplier:id,name',
        ])->findOrFail($this->id);

        foreach ($data->forecastConversionSettingPo as $po) {
            foreach ( $po['forecastConversionSettingPoQtySuppliers'] as $supplier) {
                foreach( $supplier['forecastConversionSettingPoSupplierQtyDeliveries'] as $delivery) {
                    $results[] = [
                        'item' => $po['productIngredient']['name'],
                        '1' => $delivery['date'] == 1 ? $delivery['qty'] : 0,
                        '2' => $delivery['date'] == 2 ? $delivery['qty'] : 0,
                        '3' => $delivery['date'] == 3 ? $delivery['qty'] : 0,
                        '4' => $delivery['date'] == 4 ? $delivery['qty'] : 0,
                        '5' => $delivery['date'] == 5 ? $delivery['qty'] : 0,
                        '6' => $delivery['date'] == 6 ? $delivery['qty'] : 0,
                        '7' => $delivery['date'] == 7 ? $delivery['qty'] : 0,
                        '8' => $delivery['date'] == 8 ? $delivery['qty'] : 0,
                        '9' => $delivery['date'] == 9 ? $delivery['qty'] : 0,
                        '10' => $delivery['date'] == 10 ? $delivery['qty'] : 0,
                        '11' => $delivery['date'] == 11 ? $delivery['qty'] : 0,
                        '12' => $delivery['date'] == 12 ? $delivery['qty'] : 0,
                        '13' => $delivery['date'] == 13 ? $delivery['qty'] : 0,
                        '14' => $delivery['date'] == 14 ? $delivery['qty'] : 0,
                        '15' => $delivery['date'] == 15 ? $delivery['qty'] : 0,
                        '16' => $delivery['date'] == 16 ? $delivery['qty'] : 0,
                        '17' => $delivery['date'] == 17 ? $delivery['qty'] : 0,
                        '18' => $delivery['date'] == 18 ? $delivery['qty'] : 0,
                        '19' => $delivery['date'] == 19 ? $delivery['qty'] : 0,
                        '20' => $delivery['date'] == 20 ? $delivery['qty'] : 0,
                        '21' => $delivery['date'] == 21 ? $delivery['qty'] : 0,
                        '22' => $delivery['date'] == 22 ? $delivery['qty'] : 0,
                        '23' => $delivery['date'] == 23 ? $delivery['qty'] : 0,
                        '24' => $delivery['date'] == 24 ? $delivery['qty'] : 0,
                        '25' => $delivery['date'] == 25 ? $delivery['qty'] : 0,
                        '26' => $delivery['date'] == 26 ? $delivery['qty'] : 0,
                        '27' => $delivery['date'] == 27 ? $delivery['qty'] : 0,
                        '28' => $delivery['date'] == 28 ? $delivery['qty'] : 0,
                        '29' => $delivery['date'] == 29 ? $delivery['qty'] : 0,
                        '30' => $delivery['date'] == 30 ? $delivery['qty'] : 0,
                        '31' => $delivery['date'] == 31 ? $delivery['qty'] : 0
                    ];
                }
            }
        }

        $mergedResults = [];

        foreach ($results as $result) {
            $item = $result['item'];
            if (!isset($mergedResults[$item])) {
                // Initialize a new entry if the item is not already in the merged results
                $mergedResults[$item] = $result;
            } else {
                // Merge the quantities for each day
                foreach ($result as $key => $value) {
                    if ($key !== 'item') {
                        $mergedResults[$item][$key] += $value;
                    }
                }
            }
        }

        // Convert the merged results back to a standard array if needed
        $mergedResults = array_values($mergedResults);

        return $mergedResults;
    }
}
