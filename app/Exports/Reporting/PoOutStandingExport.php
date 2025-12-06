<?php

namespace App\Exports\Reporting;

use App\Models\Purchasing\PoSupplier;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class PoOutStandingExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $month;
    protected $year;

    /**
     * @return void
     */
    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PO Out Standing';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'NO',
            'NAMA SUPPLIER',
            'NO PO',
            'TANGGAL',
            'RENCANA KEDATANGAN',
            'ITEM',
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
        $month = $this->month;
        $year = $this->year;

        $data = PoSupplier::select('id', 'purchasing_supplier_id', 'po_number', 'date', 'created_at')
            ->with([
                'purchasingSupplier:id,name',
                'poSupplierDetails:id,po_supplier_id,product_ingredient_id,product_recipe_unit_id,qty',
                'poSupplierDetails.productIngredient:id,name',
                'poSupplierDetails.productRecipeUnit:id,name',
            ]);

        if ($month) {
            $data = $data->where('month', $month);
        }

        if ($year) {
            $data = $data->where('year', $year);
        }

        $data = $data->get();

        $datas = [];
        foreach ($data as $key => $value) {

            $items = '';
            foreach ($value->poSupplierDetails as $row) {
                $name = $row->productIngredient?->name;
                $unit = $row->productRecipeUnit?->name;
                if ($items == '') {
                    $items .= $name . ' ' . $row->qty . ' ' . $unit;
                } else {
                    $items .= ', ' . $name . ' ' . $row->qty . ' ' . $unit;
                }
            }

            $datas[] = [
                'no' => $key + 1,
                'supplier_name' => $value->purchasingSupplier?->name,
                'po_number' => $value->po_number,
                'created_at' => $value->created_at_date,
                'date' => $value->date,
                'item' => $items,
            ];
        }

        return $datas;
    }
}
