<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Inventory\ProductReturn;
use App\Models\Inventory\ProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\Inventory\TransferStock;
use App\Models\Inventory\TransferStockProduct;
use App\Models\OrderProduct;
use App\Models\Pos\ClosingProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingCookie;
use App\Models\Reporting\MonitoringClosingDifferenceStock;
use App\Models\Reporting\MonitoringClosingSummary;
use App\Services\Production\CookieProductionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitoringClosingService
{

    public static function dateRange($from, $to)
    {
        return array_map(function($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }

    /**
     * Get all
     */
    public static function getAll($request)
    {
        set_time_limit(0);
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        // if ($branchId != '') {
        //     $branchId = [$branchId];
        //     MonitoringClosingSummary::whereIn('branch_id', $branchId)->where('date', '>=', $date)->where('date', '<=', $endDate)->delete();

        //     $productCategories = ProductCategory::select('id', 'name')->get();
        //     $productsAll = Product::select('id', 'name', 'product_category_id')
        //                 ->orderBy('name')
        //                 ->get();
        //     $datas = [];
        //     $dateRange = self::dateRange($date, $endDate);
        //     foreach ($dateRange as $row) {
        //         foreach ($branchId as $value) {
        //             foreach ($productCategories as $category) {
        //                 $products = $productsAll->where('product_category_id', $category->id);
        //                 $firstStock = 0;
        //                 $in = 0;
        //                 $sale = 0;
        //                 $order = 0;
        //                 $return = 0;
        //                 $transferStock = 0;
        //                 $remainsClosing = 0;
        //                 $difference = 0;
        //                 $hppTotal = 0;
        //                 foreach ($products as $rows) {
        //                     $checkFirstStock = self::firstStock($row, $value, $rows->id);
        //                     $firstStock += $checkFirstStock;

        //                     $checkIn = self::in($row, $value, $rows->id);
        //                     $in += $checkIn;

        //                     $checkSale = self::sale($row, $value, $rows->id);
        //                     $sale += $checkSale;

        //                     $checkOrder = self::order($row, $value, $rows->id);
        //                     $order += $checkOrder;

        //                     $checkReturn = self::return($row, $value, $rows->id);
        //                     $return += $checkReturn;

        //                     $checkTransferStock = self::transferStock($row, $value, $rows->id);
        //                     $transferStock += $checkTransferStock;

        //                     $checkRemainsClosing = self::remainsClosing($row, $value, $rows->id);
        //                     $remainsClosing += $checkRemainsClosing;

        //                     $checkDifference = self::difference($checkFirstStock, $checkIn, $checkSale, $checkOrder, $checkReturn, $checkTransferStock, $checkRemainsClosing);
        //                     $difference += $checkDifference;

        //                     $hppTotal += $checkDifference == 0 ? 0 : self::hppTotal($rows->id, $checkDifference);
        //                 }

        //                 $datas[] = [
        //                     'type' => $category->name,
        //                     'first_stock' => $firstStock,
        //                     'in' => $in,
        //                     'sale' => $sale,
        //                     'order' => $order,
        //                     'return' => $return,
        //                     'transfer_stock' => $transferStock,
        //                     'remains_closing' => $remainsClosing,
        //                     'difference' => $difference,
        //                     'branch_id' => $value,
        //                     'date' => $date,
        //                     'hpp_total' => $hppTotal,
        //                 ];
        //             }
        //         }
        //     }

        //     MonitoringClosingSummary::insert($datas);
        // } else {
            $datas = MonitoringClosingSummary::with(['branch:id,name'])->where('date', '>=', $date)->where('date', '<=', $endDate)->orderBy('date')->orderBy('branch_id');
            if ($branchId) {
                $datas = $datas->where('branch_id', $branchId);
            }

            $datas = $datas->get();
        // }

        return $datas;
    }

    /**
     * hppTotal
     */
    public static function hppTotal($productId, $checkDifference)
    {
        return Product::where('id', $productId)->sum('price_sale') * $checkDifference;
    }

    /**
     * Get First Stock
     */
    public static function firstStock($date, $branchId, $productId)
    {
        $dateBefore = date('Y-m-d', strtotime('-1 days', strtotime($date)));

        $datas = DB::table('closings')
                ->selectRaw('DATE(created_at) as created_at, branch_id')
                ->whereDate('created_at', $dateBefore)
                ->where('branch_id', $branchId)
                ->groupBy(DB::raw('DATE(created_at), branch_id'))
                ->havingRaw('COUNT(*) > 1')
                ->get();

        $closingIds = [];
        foreach ($datas as $value) {
            $data = DB::table('closings')
                    ->select('id')
                    ->whereDate('created_at', $value->created_at)
                    ->where('branch_id', $branchId)
                    ->orderByDesc('created_at')
                    ->first();

            $closingId = DB::table('closings')
                    ->select('id')
                    ->whereDate('created_at', $value->created_at)
                    ->where('branch_id', $branchId)
                    ->where('id', '!=', $data->id)
                    ->pluck('id');

            foreach ($closingId as $row) {
                $closingIds[] = $row;
            }
        }

        return (int) ClosingProduct::whereHas('closing', function ($query) use ($dateBefore, $closingIds, $branchId) {
                $query = $query->whereDate('created_at', $dateBefore)->whereNotIn('id', $closingIds)->where('branch_id', $branchId);
        })->where('product_id', $productId)->sum('stock_real');
    }

    /**
     * In
     */
    public static function in($date, $branchId, $productId)
    {
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual',
            'Po Brownis',
            'Po Brownis Toko'
        ];

        return ProductStockLog::select('stock')
            ->whereIn('from', $from)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', $date)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->sum('stock');
    }

    /**
     * Sale
     */
    public static function sale($date, $branchId, $productId)
    {
        return (int) OrderProduct::whereHas('orders', function ($query) use ($date, $branchId) {
            $query = $query->whereDate('created_at', $date)->cashier()->where('branch_id', $branchId);
        })
        ->where('product_id', $productId)
        ->sum('qty');
    }

    /**
     * Order
     */
    public static function order($date, $branchId, $productId)
    {
        return (int) OrderProduct::whereHas('orders', function ($query) use ($date, $branchId) {
            $query = $query->whereDate('received_date', $date)->order()->where('branch_id', $branchId);
        })
        ->where('product_id', $productId)
        ->sum('qty');
    }

    /**
     * Return & Donation
     */
    public static function return($date, $branchId, $productId)
    {
        return (int) ProductReturn::where('branch_id', $branchId)->whereDate('created_at', $date)->where('product_id', $productId)->sum('qty');
    }

    /**
     * Transfer stock
     */
    public static function transferStock($date, $branchId, $productId)
    {
        return (int) TransferStockProduct::whereHas('transferStock', function ($query) use ($date, $branchId) {
            $query = $query->whereDate('created_at', $date)->where('branch_receiver_id', $branchId)->where('status', 'delivered');
        })
        ->where('product_id', $productId)
        ->sum('qty');
    }

    /**
     * Remains Closing
     */
    public static function remainsClosing($date, $branchId, $productId)
    {
        $datas = DB::table('closings')
                ->selectRaw('DATE(created_at) as created_at, branch_id')
                ->whereDate('created_at', $date)
                ->where('branch_id', $branchId)
                ->groupBy(DB::raw('DATE(created_at), branch_id'))
                ->havingRaw('COUNT(*) > 1')
                ->get();

        $closingIds = [];
        foreach ($datas as $value) {
            $data = DB::table('closings')
                    ->select('id')
                    ->whereDate('created_at', $value->created_at)
                    ->where('branch_id', $branchId)
                    ->orderByDesc('created_at')
                    ->first();

            $closingId = DB::table('closings')
                    ->select('id')
                    ->whereDate('created_at', $value->created_at)
                    ->where('branch_id', $branchId)
                    ->where('id', '!=', $data->id)
                    ->pluck('id');

            foreach ($closingId as $row) {
                $closingIds[] = $row;
            }
        }

        return (int) ClosingProduct::whereHas('closing', function ($query) use ($date, $closingIds, $branchId) {
                $query = $query->whereDate('created_at', $date)->whereNotIn('id', $closingIds)->where('branch_id', $branchId);
        })->where('product_id', $productId)->sum('stock_real');
    }

    /**
     * Difference
     */
    public static function difference($firstStock, $in, $sale, $order, $return, $transferStock, $remainsClosing)
    {
        return (int) $remainsClosing - ($firstStock + $in - $sale - $order - $return - $transferStock);
    }

    /**
     * Difference Stock Closing
     */
    public static function getDifferenceCLosing($request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;
        if ($branchId) {
            $branchId = [$branchId];
        } else {
            $branchId = Branch::select('id')->pluck('id');
        }

        $data = ClosingProduct::select('product_id', 'product_name', 'difference', 'closing_id', 'product_code')
            ->with(['product:id,product_category_id', 'product.category:id,name', 'closing:id,branch_id,created_at'])
            ->whereHas('closing', function ($query) use ($date, $branchId, $endDate) {
                $query = $query->whereDate('created_at', '>=', $date)->whereDate('created_at', '<=', $endDate)->whereIn('branch_id', $branchId);
            })
            ->get();

        $datas = [];
        foreach ($data as $value) {
            $product = Product::select('price_sale')->find($value->product_id);
            $datas[] = [
                'branch_id' => $value->closing ? $value->closing->branch_id : null,
                'date' => $value->closing ? date('Y-m-d', strtotime($value->closing->created_at))  : null,
                'product_name' => $value->product_name,
                'product_code' =>  $value->product_code,
                'hpp_total' => $product ? $product->price_sale * $value->difference : null,
                'difference' => $value->difference,
                'product_category_name' => $value->product ? $value->product->category ? $value->product->category->name : null : null,
            ];
        }

        MonitoringClosingDifferenceStock::whereBetween('date', [$date, $endDate])->whereIn('branch_id', $branchId)->delete();
        MonitoringClosingDifferenceStock::insert($datas);

        return $datas;
    }

    /**
     * Target & Adjustment Closing
     */
    public static function getTargetCookie($request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;
        if ($branchId) {
            $branchId = [$branchId];
        } else {
            $branchId = Branch::select('id')->pluck('id');
        }

        $data = Product::select('id', 'name')->whereIn('product_category_id', config('production.cookie_categories'))->search($request)->orderBy('name')->get();

        $datas = [];
        foreach ($branchId as $row) {
            foreach ($data as $value) {
                $cookieService = app(CookieProductionService::class);
                $dateRange = self::dateRange($date, $endDate);
                $totalTarget = 0;
                $adjustment = 0;
                $firstStock = 0;
                $closing = 0;
                foreach ($dateRange as $values) {
                    // $day = date_to_day($values);
                    // $target = $cookieService->getTarget($row, $value->id, $day, $values);
                    // $buffer = $cookieService->getBuffer($row, $value->id, $day, $target);
                    $adjustment += ProductStockAdjustment::where('branch_id', $row)->where('product_id', $value->id)->whereDate('created_at', $values)->sum('qty');

                    $dateBefore = date('Y-m-d', strtotime('-1 days', strtotime($values)));
                    $closingProduct = ClosingProduct::select('stock_real')
                        ->whereHas('closing', function ($query) use ($dateBefore, $row) {
                            $query = $query->whereDate('created_at', $dateBefore)->where('branch_id', $row);
                        })
                        ->where('product_id', $value->id)
                        ->first();
                    $firstStock += $closingProduct ? $closingProduct->stock_real : 0;

                    $closingProduct2 = ClosingProduct::select('stock_real')
                        ->whereHas('closing', function ($query) use ($values, $row) {
                            $query = $query->whereDate('created_at', $values)->where('branch_id', $row);
                        })
                        ->where('product_id', $value->id)
                        ->first();
                    $closing += $closingProduct2 ? $closingProduct2->stock_real : 0;

                    $target = $cookieService->getTotalTargetFinal($row, $value->id, $values);
                    if ($target) {
                        $target = $target->grinds_sum_total;
                    } else {
                        $target = 0;
                    }

                    $totalTarget += $target;
                }

                $datas[] = [
                    'branch_id' => $row,
                    'date' => $values,
                    'product_name' => $value->name,
                    'target' => $totalTarget,
                    'adjustment' => $adjustment,
                    'first_stock' => $firstStock,
                    'closing' => $closing,
                    'realization' => $totalTarget + $adjustment
                ];
            }
        }

        MonitoringClosingCookie::whereBetween('date', [$date, $endDate])->whereIn('branch_id', $branchId)->delete();
        MonitoringClosingCookie::insert($datas);

        return $datas;
    }
}
