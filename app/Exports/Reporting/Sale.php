<?php

namespace App\Exports\Reporting;

use App\Models\OrderProduct;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class Sale implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $branchIds;
    protected $customer;

    /**
     * @return void
     */
    public function __construct($startDate, $branchIds, $customer, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchIds = $branchIds;
        $this->customer = $customer;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'HISTORY PENJUALAN';
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
            'Jenis',
            'Kode',
            'Produk',
            'Jumlah Item',
            'Total Harga',
            'Potongan',
            'Neto',
            'HPP',
            'Keterangan',
            'Perekam',
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
    public function array(): array
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $branchIds = $this->branchIds;
        $customer = $this->customer;

        $data = OrderProduct::with(['orders:id,branch_id,payment_name,created_at,customer_name,type,created_by', 'orders.createdBy:id,name', 'orders.branch:id,name', 'products.category:id,name'])
        ->whereHas('orders', function ($query) use ($startDate, $customer, $endDate, $branchIds) {
            $query = $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->where('type', 'cashier')->where('status', '!=', 'canceled');
            if ($branchIds) {
                $query = $query->whereIn('branch_id', $branchIds);
            }
            if ($customer) {
                $query = $query->where('customer_id', $customer);
            }
        })
        ->get();

        $datas = [];
        foreach ($data as $value) {
            $datas[] = [
                'branch_name' => $value->orders ? $value->orders->branch ? $value->orders->branch->name : null : null,
                'created_at' => $value->orders ? $value->orders->created_at : null,
                'product_category' => $value->products ? $value->products->first() ? $value->products->first()->category ? $value->products->first()->category ? $value->products->first()->category->name : null : null : null : null,
                'code' => $value->product_code,
                'product_name' => $value->product_name,
                'qty' => $value->qty,
                'total_price' => $value->product_price * $value->qty,
                'total_discount' => $value->discount,
                'netto' => $value->total_price,
                'product_hpp' => $value->products ? $value->products->first() ? $value->products->first()->price_sale : null : null,
                'note' => 'Penjualan',
                'created_by' => $value->orders ? $value->orders->createdBy ? $value->orders->createdBy->name : null : null,
            ];
        }

        $data = OrderProduct::with(['orders:id,branch_id,payment_name,created_at,customer_name,type,received_date,created_by', 'orders.createdBy:id,name', 'orders.branch:id,name', 'products.category:id,name'])
        ->whereHas('orders', function ($query) use ($startDate, $customer, $endDate, $branchIds) {
            $query = $query->whereDate('received_date', '>=', $startDate)->whereDate('received_date', '<=', $endDate)->where('status_pickup', 'done')->where('type', 'order')->where('status', '!=', 'canceled');
            if ($branchIds) {
                $query = $query->whereIn('branch_id', $branchIds);
            }
            if ($customer) {
                $query = $query->where('customer_id', $customer);
            }
        })
        ->get();

        foreach ($data as $value) {
            $datas[] = [
                'branch_name' => $value->orders ? $value->orders->branch ? $value->orders->branch->name : null : null,
                'created_at' => $value->orders ? $value->orders->received_date : null,
                'product_category' => $value->products ? $value->products->first() ? $value->products->first()->category ? $value->products->first()->category ? $value->products->first()->category->name : null : null : null : null,
                'code' => $value->product_code,
                'product_name' => $value->product_name,
                'qty' => $value->qty,
                'total_price' => $value->product_price * $value->qty,
                'total_discount' => $value->discount,
                'netto' => $value->total_price,
                'product_hpp' => $value->products ? $value->products->first() ? $value->products->first()->price_sale : null : null,
                'note' => 'Pesanan',
                'created_by' => $value->orders ? $value->orders->createdBy ? $value->orders->createdBy->name : null : null,
            ];
        }

        return $datas;
    }
}
