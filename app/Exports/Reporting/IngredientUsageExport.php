<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductReturn;
use App\Models\Reporting\IngredientUsage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class IngredientUsageExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $branchId;
    protected $product_category_id;

    /**
     * @return void
     */
    public function __construct($startDate, $branchId, $endDate, $product_category_id)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchId = $branchId;
        $this->product_category_id = $product_category_id;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PEMAKAIAN BAHAN';
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
            'Kode Bahan',
            'Nama Bahan',
            'Kateogri',
            'Jumlah terpakai',
            'Satuan',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->branch_name,
            $data->date,
            $data->code,
            $data->name,
            $data->product_category_name,
            $data->qty,
            $data->unit_name,
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
        $data = IngredientUsage::whereIn('branch_id', $this->branchId)
            ->where('date', '>=', $this->startDate)
            ->where('date', '<=', $this->endDate);

        if ($this->product_category_id) {
            $data = $data->where('product_category_id', $this->product_category_id);
        }

        return $data;
    }
}
