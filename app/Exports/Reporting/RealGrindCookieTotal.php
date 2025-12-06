<?php

namespace App\Exports\Reporting;

use App\Models\Branch;
use App\Models\Inventory\ProductStockLog;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Production\RealGrindCookie;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class RealGrindCookieTotal implements FromArray, WithHeadings, WithStyles, WithTitle
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
        return 'REAL GILING ROTI MANIS TOTAL';
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
            'Jumlah Giling',
            'Hasil Giling',
            'Jumlah Press',
            'Press Gram',
            'Gram',
            'Jumlah Masuk',
            'Selisih Rekon',
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

        $result = [];
        $date = $this->dateRange($date, $endDate);
        foreach ($date as $row) {
            foreach ($branchId as $value) {
                $data = RealGrindCookie::where('date', $row)->with(['createdBy:id,name'])->where('branch_id', $value->id)->get();
                $totalPressBread = $data->where('type', 'bread')->sum('total_press');
                $totalPressCookie = $data->where('type', 'cookie')->sum('total_press');
                $gramPressBread = $totalPressBread * 36 * 50;
                $gramPressCookie = $totalPressCookie * 36 * 50;
                $totalIncomingBread = $this->totalIncoming($value->id, $row, 'bread');
                $totalIncomingCookie = $this->totalIncoming($value->id, $row, 'cookie');
                $totalGramBread = $data->where('type', 'bread')->sum('gram_unit');
                $totalGramCookie = $data->where('type', 'cookie')->sum('gram_unit');

                $result[] =  [
                    'branch_name' => $value->name,
                    'date' => $row,
                    'type' => 'ROTI TAWAR',
                    'total_grind' => $data->where('type', 'bread')->sum('grind_unit'),
                    'result_grind' => $data->where('type', 'bread')->sum('total_product'),
                    'total_press' => $totalPressBread,
                    'gram_press' => $gramPressBread,
                    'gram' => $totalGramBread,
                    'total_incoming' => $totalIncomingBread,
                    'adjustment' => $totalIncomingBread - $totalGramBread,
                ];

                $result[] = [
                    'branch_name' => $value->name,
                    'date' => $row,
                    'type' => 'ROTI MANIS',
                    'total_grind' => $data->where('type', 'cookie')->sum('grind_unit'),
                    'result_grind' => $data->where('type', 'cookie')->sum('total_product'),
                    'total_press' => $totalPressCookie,
                    'gram_press' => $gramPressCookie,
                    'gram' => $totalGramCookie,
                    'total_incoming' => $totalIncomingCookie,
                    'adjustment' => $totalIncomingCookie - $totalGramCookie,
                ];
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
    public function totalIncoming($branchId, $date, $type)
    {
        if ($type == 'cookie') {
            $categoryIds = config('production.cookie_categories');
        } else {
            $categoryIds = config('production.bread_categories');
        }

        $productIds = Product::whereIn('product_category_id', $categoryIds)->pluck('id');
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual'
        ];

        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'from', 'created_by', 'created_at')
            ->with(['product:id,name,code,product_category_id,gramasi'])
            ->whereIn('from', $from)
            ->whereIn('product_id', $productIds)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', $date)
            ->where('branch_id', $branchId)
            ->get();

        $totalGramasi = 0;
        foreach ($data as $value) {
            $gramasi = $value->product ? $value->product->gramasi : null;
            $totalGramasi = $totalGramasi + ($value->stock * $gramasi);
        }

        return $totalGramasi;
    }

    public function dateRange($from, $to)
    {
        return array_map(function($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }
}
