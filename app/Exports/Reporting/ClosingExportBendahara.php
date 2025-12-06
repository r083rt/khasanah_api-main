<?php

namespace App\Exports\Reporting;

use App\Models\Pos\ClosingExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClosingExportBendahara implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $date;
    protected $branchId;

    /**
     * @return void
     */
    public function __construct($date, $branchId)
    {
        $this->date = $date;
        $this->branchId = $branchId;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'BENDAHARA';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Ket',
            'Cabang',
            'Lokal System',
            'System Pusat',
            'Setoran perKasir',
            'Selisih Setoran',
            'Total Biaya',
            'Cicilan Cash',
            'Cicilan NonCash',
            'Penjualan Cash',
            'Penjualan NonCash',
            'Uang Modal',
            'Selisih Lokal dan Pusat',
            'DP Pesanan Cash',
            'DP Pesanan NonCash',
            'DP Pengambilan Cash',
            'DP Pengambilan NonCash',
            'Piutang',
            'Kasir',
            'Jam',
            'Status',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->note,
            $data->branch_name,
            $data->local_system,
            $data->central_system,
            $data->cashier_income,
            $data->deposit_difference,
            $data->cost,
            $data->payment_cash,
            $data->payment_noncash,
            $data->sales_cash,
            $data->sales_noncash,
            $data->initial_capital,
            $data->local_central_difference,
            $data->dp_cash_order,
            $data->dp_noncash_order,
            $data->dp_cash_withdrawal,
            $data->dp_noncash_withdrawal,
            $data->credit,
            $data->created_by_name,
            $data->date,
            $data->status,
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
        return ClosingExport::where('user_id', Auth::id());
    }
}
