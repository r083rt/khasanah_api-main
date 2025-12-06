<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductReturn;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class HistoryReturn implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return 'HISTORY RETUR DAN SUMBANGAN';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Cabang',
            'Tipe',
            'Jenis',
            'Kode',
            'Item',
            'Qty',
            'Total Harga',
            'Total HPP',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->created_at ? date('Y-m-d', strtotime($data->created_at)) : null,
            $data->branch ? $data->branch->name : null,
            $data->type_indo,
            $data->product ? $data->product->category ? $data->product->category ? $data->product->category->name : null : null : null,
            $data->product ? $data->product->code : null,
            $data->product ? $data->product->name : null,
            $data->qty,
            $data->total_price,
            $data->total_hpp,
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
        $data = ProductReturn::select('branch_id', 'product_id', 'qty', 'type', 'created_at', 'total_price', 'total_hpp')->with(['product:id,name,price,code,product_category_id', 'branch:id,name', 'product.category:id,name'])
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        if ($this->branchId) {
            $data = $data->where('branch_id', $this->branchId);
        }

        return $data;
    }
}
