<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductStockLog;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class MutationStock implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $date;
    protected $branchId;
    protected $productId;

    /**
     * @return void
     */
    public function __construct($date, $branchId, $productId)
    {
        $this->date = $date;
        $this->branchId = $branchId;
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'MUTASI STOK';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Barang',
            'Stok Before',
            'Qty',
            'Stok After',
            'Tanggal',
            'Kasir',
            'Sumber',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->branch ? $data->branch->name : null,
            $data->product ? $data->product->name : null,
            $data->stock_old,
            $data->stock,
            $data->stock_after,
            $data->created_at->format('Y-m-d H:i:s'),
            $data->createdBy ? $data->createdBy->name : null,
            $data->from,
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
        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'stock_old', 'from', 'created_by', 'created_at')->with(['product:id,name', 'branch:id,name', 'createdBy:id,name'])
        ->whereDate('created_at', $this->date);

        if ($this->productId) {
            $data = $data->where('product_id', $this->productId);
        }

        if ($this->branchId) {
            $data = $data->where('branch_id', $this->branchId);
        }

        return $data;
    }
}
