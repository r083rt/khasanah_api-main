<?php

namespace App\Exports\Reporting;

use App\Models\Distribution\PoSjItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;

class PoTravelDoc implements FromArray, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $branchId;

    /**
     * @return void
     */
    public function __construct($startDate, $branchId, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchId = $branchId;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'REALISASI PO SURAT JALAN';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Tanggal PO',
            'Kode Barang',
            'Produk',
            'Jenis',
            'Qty Po',
            'Qty Unit Pengiriman',
            'Qty Real',
            'Qty Pengiriman',
            'Nomor Surat Jalan',
            'Tanggal Diterima',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data['branch'] ? $data['branch']['name'] : null,
            $data['po_date'],
            $data['code_item'],
            $data['name_item'],
            $data['product'] ? $data['product']['category'] ? $data['product']['category']['name'] : 'BAHAN' : 'BAHAN',
            $data['qty'],
            $data['qty_unit_delivery'],
            $data['qty_real'],
            $data['qty_delivery'],
            $data['posj'] ? $data['posj']['sj_number'] : null,
            $data['received_date']
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
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $branch = $this->branchId;

        $data = PoSjItem::with(['posj', 'branch:id,name', 'product:id,name,product_category_id', 'product.category:id,name'])->whereHas('posj', function ($query) use ($branch, $startDate, $endDate) {
            $query = $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
            if ($branch) {
                $query = $query->where('branch_id', $branch);
            }
        });

        $data = $data->get();
        foreach ($data as $key => $value) {
            if ($value->product_id) {
                $product = Product::find($value->product_id);
                if ($product) {
                    if ($product->unit_value == 0 || is_null($product->unit_value)) {
                        $value->qty_unit_delivery = $value->qty;
                    } else {
                        $qty = $value->qty / $product->unit_value;
                        $value->qty_unit_delivery = $qty;
                    }
                } else {
                    $value->qty_unit_delivery = null;
                }
            } else {
                $product = ProductIngredient::find($value->product_ingredient_id);
                if ($product) {
                    if ($product->unit_value == 0 || is_null($product->unit_value)) {
                        $value->qty_unit_delivery = $value->qty;
                    } else {
                        $qty = $value->qty / $product->unit_value;
                        $value->qty_unit_delivery = $qty;
                    }
                } else {
                    $value->qty_unit_delivery = null;
                }
            }
        }

        return $data->toArray();
    }
}
