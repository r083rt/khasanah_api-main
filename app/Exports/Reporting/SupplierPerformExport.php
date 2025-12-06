<?php

namespace App\Exports\Reporting;

use App\Models\Purchasing\PoSupplier;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupplierPerformExport implements FromArray, WithHeadings, WithStyles, WithTitle
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
        return 'Performa Supplier';
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
            'RENCANA DATANG',
            'TANGGAL TERIMA',
            'DEVIASI HARI(+/-)',
            'QTY PO',
            'QTY BTB',
            'DEVIASI QTY(+/-)',
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

        $data = PoSupplier::select('id', 'purchasing_supplier_id', 'po_number', 'date', 'received_at')
            ->with([
                'purchasingSupplier:id,name',
                'poSupplierDetails:id,po_supplier_id,product_ingredient_id,product_recipe_unit_id,qty,qty_received',
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

            $qty_po = 0;
            $qty_btb = 0;
            foreach ($value->poSupplierDetails as $row) {
                $qty_po += $row->qty;
                $qty_btb += $row->qty_received;
            }

            $datas[] = [
                'no' => $key + 1,
                'supplier_name' => $value->purchasingSupplier?->name,
                'date' => $value->date,
                'received_at' => $value->received_at,
                'deviation_date' => $value->day_deviation,
                'qty_po' => $qty_po,
                'qty_btb' => $qty_btb,
                'deviation_qty' => $qty_btb - $qty_po,
            ];
        }

        return $datas;
    }
}
