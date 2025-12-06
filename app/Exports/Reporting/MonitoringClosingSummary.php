<?php

namespace App\Exports\Reporting;

use App\Models\Reporting\MonitoringClosingSummary as ReportingMonitoringClosingSummary;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringClosingSummary implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return 'SUMMARY';
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
            'Jenis',
            'Stok Awal',
            'Masuk',
            'Jual',
            'Pesanan',
            'Sumbangan',
            'Transfer Stok',
            'Stok Akhir',
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
            $data->type,
            $data->first_stock,
            $data->in,
            $data->sale,
            $data->order,
            $data->return,
            $data->transfer_stock,
            $data->remains_closing,
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
        $data = ReportingMonitoringClosingSummary::select('type', 'first_stock', 'in', 'sale', 'order', 'return', 'transfer_stock', 'remains_closing', 'difference', 'date', 'branch_id', 'hpp_total')
            ->with(['branch:id,name'])
            ->whereBetween('date', [$this->date, $this->endDate]);

        if ($this->branchId) {
            $data = $data->where('branch_id', $this->branchId);
        }

        return $data;
    }
}
