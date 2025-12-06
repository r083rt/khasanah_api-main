<?php

namespace App\Exports\Reporting;

use App\Models\Branch;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Production\RealGrindBrowniesStore;
use App\Models\Production\RealGrindCookie;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class RealGrindBrowniesStoreTotal implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $date;
    protected $endDate;
    protected $branchId;

    /**
     * @return void
     */
    public function __construct($date, $branchId, $endDate)
    {
        $this->date = $date;
        $this->endDate = $endDate;
        $this->branchId = $branchId;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'REAL GILING BROWNIES TOKO TOTAL';
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
            'Detil Item',
            'Jumlah Giling',
            'Hasil Giling',
            'Gramasi',
            'Jumlah Estimasi Produk',
            'Total Produk',
            'Jumlah Masuk',
            'Selisih Rekon',
            'Total PCS',
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
        $date = $this->date;
        $endDate = $this->endDate;
        $branchId = $this->branchId;

        if ($branchId) {
            $branchId =  Branch::select('id', 'name')->where('id', $branchId)->get();
        } else {
            if (Auth::user()->branch_id != 1) {
                $branchId =  Branch::select('id', 'name')->where('id', Auth::user()->branch_id)->get();
            } else {
                $branchId =  Branch::select('id', 'name')->get();
            }
        }

        $paketan = Packaging::with(['products'])->get();

        $result = [];
        $date = $this->dateRange($date, $endDate);
        foreach ($date as $row) {
            $data = RealGrindBrowniesStore::where('date', $row)->get();
            foreach ($branchId as $value) {
                $data = $data->where('branch_id', $value->id);
                foreach ($paketan as $packaging) {
                    $productIds = $packaging->products->pluck('id');
                    $totalGramasiPackaging = $packaging->gramasi;

                    switch ($packaging->type) {
                        case 'brownies':
                            $type = 'BROWNIES';
                            break;

                        case 'sponge':
                            $type = 'BOLU';
                            break;

                        case 'cake':
                            $type = 'CAKE';
                            break;

                        default:
                            $type = null;
                            break;
                    }

                    $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($row)));
                    $totalIncoming = $this->totalIncoming($dateNext, $value->id, $productIds)['gramasi'];
                    $totalQty = $this->totalIncoming($dateNext, $value->id, $productIds)['qty'];

                    $qtyEstimation = $data->where('master_packaging_id', $packaging->id)->sum('qty_estimation');
                    $totalGrind = $data->where('master_packaging_id', $packaging->id)->sum('grind_unit');
                    $totalGramasiPackaging = $totalGramasiPackaging * $totalGrind;
                    $adjustment = $totalIncoming - ($totalGramasiPackaging);

                    $totalPcs = 0;
                    if ($totalGramasiPackaging != 0) {
                        $totalPcs = $adjustment / $totalGramasiPackaging;
                    }

                    $result[] =  [
                        'branch_name' => $value->name,
                        'date' => $row,
                        'type' => $type,
                        'detil_item' => $packaging->name,
                        'total_grind' => $totalGrind,
                        'result_grind' => $data->where('master_packaging_id', $packaging->id)->sum('qty_real'),
                        'gramasi' => $totalGramasiPackaging,
                        'qty_estimation' => $qtyEstimation,
                        'qty_real' => $totalQty,
                        'total_incoming' => $totalIncoming,
                        'adjustment' => $adjustment,
                        'total_pcs' => rounding_real_grind($totalPcs),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Total Incoming
     *
     * @param integer $branchId
     * @param string $date
     * @return integer
     */
    public function totalIncoming($date, $branchId, $productIds)
    {
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual',
            'Po Brownis',
            'Po Brownis Toko'
        ];

        $data = ProductStockLog::select('id', 'product_id', 'stock')
            ->with(['product:id,name,gramasi'])
            ->whereIn('from', $from)
            ->whereIn('product_id', $productIds)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', $date)
            ->where('branch_id', $branchId)
            ->get();

        $qty = 0;
        $totalGramasi = 0;
        foreach ($data as $value) {
            $gramasi = $value->product ? $value->product->gramasi : null;
            $gramasi_conversion = $value->stock * $gramasi;

            $qty += $value->stock;
            $totalGramasi += $gramasi_conversion;
        }

        return [
            'qty' => $qty,
            'gramasi' => $totalGramasi,
        ];
    }

    public function dateRange($from, $to)
    {
        return array_map(function($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }
}
