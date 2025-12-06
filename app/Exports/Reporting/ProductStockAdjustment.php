<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\ProductStockAdjustment as InventoryProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\Prefix;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductStockAdjustment implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return 'HISTORY BARANG MASUK';
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
            'Kode',
            'Item',
            'Jumlah',
            'Gram',
            'Chanel',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        $gramasi = $data->product ? $data->product->gramasi : null;
        return [
            $data->created_at ? $data->created_at->format('Y-m-d') : null,
            $data->branch ? $data->branch->name : null,
            $data->product ? $data->product ? $data->product->category ? $data->product->category->name : null : null : null,
            $data->product ? $data->product->code : null,
            $data->product ? $data->product->name : null,
            $data->stock,
            $data->stock * $gramasi,
            $data->from,
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
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual',
            'Po Brownis',
            'Po Brownis Toko'
        ];

        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'from', 'created_by', 'created_at')
            ->with(['product:id,name,code,product_category_id,gramasi', 'branch:id,name', 'createdBy:id,name', 'product.category:id,name'])
            ->whereIn('from', $from)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        if ($this->branchIds) {
            $data = $data->whereIn('branch_id', $this->branchIds);
        }

        return $data;
    }
}
