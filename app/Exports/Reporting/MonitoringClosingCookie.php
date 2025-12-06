<?php

namespace App\Exports\Reporting;

use App\Models\Reporting\MonitoringClosingCookie as ReportingMonitoringClosingCookie;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringClosingCookie implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return 'TARGET DAN PENYESUAIAN ROTI MANIS';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Produk',
            'Stok Awal',
            'Sisa',
            'Target',
            'Penyesuaian',
            'Realisasi',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->product_name,
            $data->first_stock,
            $data->closing,
            $data->target,
            $data->adjustment,
            $data->realization,
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
        $data = ReportingMonitoringClosingCookie::select('product_name', 'target', 'adjustment', 'first_stock', 'closing', 'realization')
            ->whereBetween('date', [$this->date, $this->endDate]);

        if ($this->branchId) {
            $data = $data->where('branch_id', $this->branchId);
        }

        return $data;
    }
}
