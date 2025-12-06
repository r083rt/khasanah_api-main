<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductStockAdjustment as InventoryProductStockAdjustment;
use App\Models\Pos\Expense as PosExpense;
use App\Models\Prefix;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class Expense implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return 'HISTORY BIAYA';
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
            'Nomor',
            'Master Biaya',
            'Biaya',
            'Jumlah',
            'Total Biaya',
            'Tipe',
            'Item',
            'Keterangan',
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
            $data->created_at->format('Y-m-d'),
            $data->master ? $data->master->nomor : null,
            $data->master ? $data->master->name : null,
            $data->cost,
            $data->qty,
            $data->total_cost,
            $data->category == 'ingredient' ? 'Belanja' : 'Biaya lain',
            $data->ingredient ? $data->ingredient->name : null,
            $data->ingredient ? null : $data->note,
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
        $data = PosExpense::with(['createdBy:id,name', 'branch:id,name', 'ingredient:id,name', 'master:id,nomor,name'])->whereDate('created_at', '>=', $this->startDate)->whereDate('created_at', '<=', $this->endDate);
        if ($this->branchIds) {
            $data = $data->whereIn('branch_id', $this->branchIds);
        }

        return $data;
    }
}
