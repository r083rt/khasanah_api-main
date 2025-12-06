<?php

namespace App\Exports\Reporting;

use App\Models\Order as ModelsOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class Order implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;

    /**
     * @return void
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'HISTORY PESANAN';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Jenis',
            'Keterangan',
            'Pelanggan',
            'Tanggal Pesan',
            'Tanggal Ambil',
            'Tanggal Penyerahan',
            'Item + Qty',
            'Perekam',
            'Status Pengambilan',
            'Status Pembayaran',
            'Link Detail',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        $products = [];
        foreach ($data->products as $row) {
            $products[] = $row->product_name . ' (' . $row->qty . ')';
        }
        $product_name = implode(', ', $products);

        return [
            $data->branch ? $data->branch->name : null,
            $data->category ? $data->category->name : null,
            $data->note,
            $data->customer_name,
            $data->created_at ? $data->created_at->format('Y-m-d') : null,
            $data->date_pickup,
            $data->received_date ? date('Y-m-d', strtotime($data->received_date)) : 'Belum diambil',
            $product_name,
            $data->createdBy ? $data->createdBy->name : null,
            $data->status_pickup_indo,
            $data->status_payment_indo,
            env("PUBLIC_URL") . "/pos/summary-orders/detail/" . $data->id
        ];
    }

    /**
    *
    * @return array
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Get data to be export
     *
     * @return Collection
     */
    public function query()
    {
        return ModelsOrder::select('id', 'branch_id', 'product_category_id', 'customer_name', 'note', 'created_at', 'date_pickup', 'received_date', 'created_by', 'status_pickup', 'status_payment')
            ->order()
            ->with(['branch:id,name', 'category:id,name', 'createdBy:id,name', 'products:id,order_id,product_name,qty'])
            ->where('date_pickup', '>=', $this->startDate)
            ->where('date_pickup', '<=', $this->endDate);
    }
}
