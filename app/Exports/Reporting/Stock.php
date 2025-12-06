<?php

namespace App\Exports\Reporting;

use App\Models\OrderProduct;
use App\Models\Pos\ClosingProduct;
use App\Models\Pos\Expense as PosExpense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class Stock implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $branchIds;

    /**
     * @return void
     */
    public function __construct($startDate, $branchIds, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchIds = $branchIds;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'HISTORY STOK';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Harga',
            'Kode Barang',
            'Nama Barang',
            'Jenis',
            'System',
            'Manual',
            'Selisih',
            'Jam',
            'Perekam',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->closing ? $data->closing->branch ? $data->closing->branch->name : null : null,
            $data->product ? $data->product->price : null,
            $data->product_code,
            $data->product_name,
            $data->product ? $data->product->category ? $data->product->category ? $data->product->category->name : null : null : null,
            $data->stock_system,
            $data->stock_real,
            $data->difference,
            $data->created_at->format('Y-m-d'),
            $data->closing ? $data->closing->createdBy ? $data->closing->createdBy->name : null : null,
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
        return ClosingProduct::with(['closing', 'closing.createdBy:id,name', 'product:id,price,product_category_id', 'product.category:id,name', 'closing.branch:id,name'])
        ->whereHas('closing', function ($query) {
            $query = $query->whereDate('created_at', '>=', $this->startDate)->whereDate('created_at', '<=', $this->endDate);
            if ($this->branchIds) {
                $query = $query->whereIn('branch_id', $this->branchIds);
            }
        });
    }
}
