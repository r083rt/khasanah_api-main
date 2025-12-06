<?php

namespace App\Exports\Reporting;

use App\Models\Reporting\MonitoringClosingDifferenceStock as ReportingMonitoringClosingDifferenceStock;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringClosingDifferenceStock implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $date;
    protected $branchId;
    protected $endDate;

    /**
     * @return void
     */
    public function __construct($date, $branchId, $endDate)
    {
        $this->date = $date;
        $this->branchId = $branchId;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'SELISIH STOK CLOSING';
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
            'Kode Item',
            'Nama Item',
            'Kategori Item',
            'Selisih',
            'HPP',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->date,
            $data->branch ? $data->branch->name : null,
            $data->product_code,
            $data->product_name,
            $data->product_category_name,
            $data->difference,
            $data->hpp_total
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
        $data = ReportingMonitoringClosingDifferenceStock::select('product_name', 'product_code', 'product_category_name', 'difference', 'hpp_total', 'branch_id', 'date')
            ->with(['branch:id,name'])
            ->whereBetween('date', [$this->date, $this->endDate]);

        if ($this->branchId) {
            $data = $data->where('branch_id', $this->branchId);
        }

        return $data;
    }
}
