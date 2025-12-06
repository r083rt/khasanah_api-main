<?php

namespace App\Exports\Distribution;

use App\Models\Distribution\PoManualDetail;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Pos\ClosingProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class PoManual implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;

    /**
     * @return void
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PO MANUAL';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Tanggal',
            'Barcode',
            'Produk',
            'Jenis',
            'Qty',
            'Satuan',
            'Nomor PO',
            'Chanel',
            'Status',
            'Perekam',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        if ($data->product) {
            $code = $data->product ? $data->product->code : null;
            $name = $data->product ? $data->product->name : null;
            $category = $data->product ? $data->product ? $data->product->category ? $data->product->category->name : null : null : null;
            $unit = $data->product ? $data->product->unitDelivery ? $data->product->unitDelivery->name : null : null;
        } else {
            $barcode = ProductIngredientBrand::select('barcode')->where('product_ingredient_id', $data->product_ingredient_id)
                ->where('product_recipe_unit_id', $data->product_ingredient_unit_id)
                ->first();
            $code = $barcode?->barcode;

            $name = $data->ingredient ? $data->ingredient->name : null;
            $category = "BAHAN";
            $unit = $data->unit ? $data->unit->name : null;
        }

        return [
            $data->poManual ? $data->poManual->branch ? $data->poManual->branch->name : null : null,
            $data->poManual ? $data->poManual->created_at : null,
            $code,
            $name,
            $category,
            $data->qty,
            $unit,
            $data->poManual ? $data->poManual->nomor_po : null,
            "Manual Input",
            "Po Diterima",
            $data->poManual ? $data->poManual->createdBy ? $data->poManual->createdBy->name : null : null,
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
    public function query()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        return PoManualDetail::with([
                'poManual:id,nomor_po,created_at,created_by,status,branch_id',
                'poManual.branch:id,name',
                'poManual.createdBy:id,name',
                'product:id,name,code,product_category_id,product_unit_delivery_id',
                'product.category:id,name',
                'product.unitDelivery:id,name',
                'ingredient:id,name,code',
                'unit:id,name'
            ])
            ->whereHas('poManual', function ($query) {
                $query = $query->where('status', 'po-accepted');
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
    }
}
