<?php

namespace App\Services\Production;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Pos\Closing;
use Illuminate\Support\Carbon;
use App\Models\Production\CookieSale;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use App\Models\Production\CookieProduction;
use App\Models\Production\CookieBufferTarget;
use App\Models\Production\CookieProductionGrind;
use App\Models\Distribution\PoOrderProductDetail;
use App\Models\OrderProduct;
use App\Models\Pos\ClosingProduct;
use App\Models\Production\CookieBufferProduction;
use App\Models\Production\BrowniesTargetPlanProduction;

class CookieProductionService
{
    /**
     * Get Production
     *
     * @param string $date
     * @param string $day
     * @return int
     */
    public function getProduction($date, $branchId)
    {
        $day = date_to_day($date);
        $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($date)));
        $dayNext = date_to_day($dateNext);
        $checkData = $this->checkData($date, $branchId);
        if ($checkData->count() > 0) {
            foreach ($checkData as $value) {
                $value->total_grinds = $value->grinds->count();
                $total_target_after_grind = 0;
                foreach ($value->grinds as $row) {
                    $total_target_after_grind = $total_target_after_grind + $row->total;
                }
                $value->total_target_after_grind = $total_target_after_grind;
            }
            return [
                'is_editable' => false,
                'data' => $checkData->sortByDesc('total_target_after_remains')->values()->all()
            ];
        } else {
            $productService = new ProductService();
            $products = $productService->getAllCookie(true, $branchId, $day);
            $totalAllTargetRemains = 0;
            foreach ($products as $value) {
                $branch = Branch::find($branchId);
                $target = $this->getTarget($branchId, $value->id, $dayNext, $dateNext);
                $buffer = $this->getBuffer($branchId, $value->id, $dayNext, $target);
                $order = $this->getOrder($branchId, $value->id, $date);
                $remains = $this->getRemains($branchId, $value->id, $date);
                if ($remains == 0 || is_null($remains)) {
                    $remains = $this->getRemains($branchId, $value->id, $date);
                }
                $totalTarget = $this->getTotalTarget($target, $buffer, $remains);
                $totalTargetAfterRemains = $this->getTotalTargetAfterRemains($target, $buffer, $remains, $totalTarget);

                $value->product_id = $value->id;
                $value->branch_id = $branchId;
                $value->branch_name = $branch->name;
                $value->target = $target;
                $value->buffer = $buffer;
                $value->order = $order;
                $value->remains = $remains;
                $value->total_target = $totalTarget;
                $value->total_target_after_remains = $totalTargetAfterRemains;
                $totalAllTargetRemains = $totalAllTargetRemains + $totalTargetAfterRemains;
                $value->total_target_after_grind = 0;
            }

            foreach ($products as $value) {
                $value->real_grinds = $this->calculateTotalGrind($totalAllTargetRemains);
                $value->total_grinds = ceil($value->real_grinds);
            }

            return [
                'is_editable' => $products->count() == 0 ? false : true,
                'data' => $products->sortByDesc('total_target_after_remains')->values()->all()
            ];
        }
    }

    /**
     * calculateTotalGrind
     *
     * @param integer $totalAllTargetRemains
     * @return double
     */
    public function calculateTotalGrind($totalAllTargetRemains)
    {
        $total = $totalAllTargetRemains / config('production.total_grind');
        $floor = floor($total);
        $fraction = (float)substr($total - $floor, 0, 3);
        if ($fraction <= 0.2) {
            return $floor;
        } elseif ($fraction >= 0.3 && $fraction <= 0.7) {
            return $floor + 0.5;
        } else {
            return $floor + 1;
        }
    }

    /**
     * Get Check data
     *
     * @param date $date
     * @param integer $branchId
     * @return collection
     */
    public function checkData($date, $branchId)
    {
        return CookieProduction::with(['grinds'])->where('date', $date)->where('branch_id', $branchId)->get();
    }

    /**
     * Get Check data calculating
     *
     * @param date $date
     * @param integer $branchId
     * @return collection
     */
    public function checkDataCalculating($date, $branchId)
    {
        return CookieProduction::where('date', $date)->where('branch_id', $branchId)->where('status', 'calculating')->count();
    }

    /**
     * Update Status
     *
     * @param date $date
     * @param integer $branchId
     * @param string $status
     * @return collection
     */
    public function updateStatus($date, $branchId, $status)
    {
        return CookieProduction::with(['grinds'])->where('date', $date)->where('branch_id', $branchId)->update(['status' => $status]);
    }

    /**
     * Create data
     *
     * @param array $data
     * @return collection
     */
    public function create($data)
    {
        return CookieProduction::create($data);
    }

    /**
     * Get Target
     *
     * @param integer $branchId
     * @param integer $productId
     * @param string $day
     * @param string $date
     * @return int
     */
    public function getTarget($branchId, $productId, $day, $date)
    {
        $data = CookieSale::select('target')->where('branch_id', $branchId)->where('product_id', $productId)->where('day', $day)->first();

        if ($data) {
            $date = explode('-', $date);
            $day = $date[2];
            $month = $date[1];
            $year = $date[0];
            $bufferTarget = CookieBufferTarget::select('buffer')->where('branch_id', $branchId)->where('product_id', $productId)->where('date_day', $day)->where('date_month', $month)->where('date_year', $year)->first();
            if ($bufferTarget && $bufferTarget->buffer != 0) {
                $buffer = ($bufferTarget->buffer / 100 * $data->target);
                return round($data->target + $buffer);
            }

            return $data->target;
        }

        return 0;
    }

    /**
     * Get Buffer
     *
     * @param integer $branchId
     * @param integer $productId
     * @param string $day
     * @param int $target
     * @return int
     */
    public function getBuffer($branchId, $productId, $day, $target)
    {
        $bufferProduksi = CookieBufferProduction::select('buffer')->where([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'day' => $day,
        ])->first();

        if ($bufferProduksi && $bufferProduksi->buffer != 0) {
            return round($bufferProduksi->buffer / 100 * $target);
        }

        return 0;
    }

    /**
     * Get Order
     *
     * @param integer $branchId
     * @param integer $productId
     * @param string $day
     * @return int
     */
    public function getOrder($branchId, $productId, $date)
    {
        $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($date)));
        return (int) OrderProduct::select('qty')
                    ->where('product_id', $productId)
                    ->whereHas('orders', function ($query) use ($dateNext, $branchId) {
                        $query = $query->where('type', 'order')->where('date_pickup', $dateNext)->where('branch_id', $branchId);
                    })
                    ->sum('qty');
    }

    /**
     * Get Remains
     *
     * @param integer $branchId
     * @param integer $productId
     * @param string $day
     * @return int
     */
    public function getRemains($branchId, $productId, $date)
    {
        $closing = Closing::select('id')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->first();

        if ($closing) {
            $data = ClosingProduct::select('stock_real')
                ->where('product_id', $productId)
                ->where('closing_id', $closing->id)
                ->first();

            if ($data) {
                return $data->stock_real;
            }
        }

        return 0;
    }

    /**
     * Get Total Target
     *
     * @param integer $target
     * @param integer $buffer
     * @param integer $remains
     * @return integer
     */
    public function getTotalTarget($target, $buffer, $remains)
    {
        $data = (int)($target + $buffer - $remains);
        if ($data < 0) {
            $data = 0;
        }

        return $data;
    }

    /**
     * Get Total Target After Remains
     *
     * @param integer $target
     * @param integer $buffer
     * @param integer $remains
     * @param integer $totalTarget
     * @return integer
     */
    public function getTotalTargetAfterRemains($target, $buffer, $remains, $totalTarget)
    {
        if ($totalTarget == 0) {
            return $totalTarget;
        }

        if ($remains == 0) {
            return $target + ($buffer * 2);
        }

        return $totalTarget;
    }

    /**
     * Get Total Target Final
     *
     * @param integer $branchId
     * @param integer $productId
     * @param string $date
     * @return integer
     */
    public function getTotalTargetFinal($branchId, $productId, $date)
    {
        return CookieProduction::where('branch_id', $branchId)->where('date', $date)->where('product_id', $productId)->withSum('grinds', 'total')->first();
    }

    /**
     * Calculate Total Grinds
     *
     * @param integer $totalGrind
     * @param double $realGrind
     * @param date $date
     * @param integer $branchId
     * @param integer $totalQty
     * @return integer
     */
    public function calculateGrind($totalGrind, $realGrind, $date, $branchId, $totalQty)
    {
        $model = CookieProduction::where([
            'date' => $date,
            'branch_id' => $branchId,
        ])->orderBy('total_target_after_remains', 'DESC')->get();

        $config = config('production.total_grind');
        $totalAll = 0;
        $skipProducts = [];
        $grindStorage = [];
        for ($i = 1; $i <= $totalGrind; $i++) {
            $total = 0;
            $totalGrinds = 0;
            while ($total < $totalQty) {
                if ($totalGrinds <= $config) {
                    foreach ($model as $value) {
                        $grind = $grindStorage[$value->id][$i] ?? null;
                        $grindTotal = array_sum(array_values($grindStorage[$value->id] ?? []));
                        if (is_null($grind)) {
                            //create
                            if (!in_array($value->product_id, $skipProducts) && $value->total_target_after_remains > 0) {
                                $grindStorage[$value->id][$i] = 1;
                                $totalGrinds = $totalGrinds + 1;
                                $totalAll = $totalAll + 1;
                            } else {
                                $grindStorage[$value->id][$i] = 0;
                            }
                        } else {
                            //update
                            if (!in_array($value->product_id, $skipProducts)) {
                                if ((($grindTotal) < $value->total_target_after_remains) && ($totalGrinds < $config) && ($totalAll < $totalQty)) {
                                    //jika gilingan tidak melebih total target setelah sisa semalam
                                    //total gilingan keseluruhan produk dibawah 216
                                    //total gilingan keseluruhan giligan < total jumlah produksi
                                    $grindStorage[$value->id][$i] = $grindStorage[$value->id][$i] + 1;

                                    $totalGrinds = $totalGrinds + 1;
                                    $totalAll = $totalAll + 1;
                                } else {
                                    if (($grindTotal + 1) > $value->total_target_after_remains) {
                                        $skipProducts[] = $value->product_id;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $total = $totalQty;
                }

                $total = $total + 1;
            }
        }

        $batchInsert = collect();
        foreach ($grindStorage as $cookie_production_id => $grinds) {
            foreach ($grinds as $grind => $total) {
                $batchInsert->push([
                    'cookie_production_id' => $cookie_production_id,
                    'grind' => $grind,
                    'total' => $total,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        $batchInsert = $batchInsert->where('grind', '!=', $totalGrind)->merge(
            $this->adjustmentLastGrind(
                $realGrind,
                $batchInsert
                    ->where('grind', '=', $totalGrind)
                    ->toArray(),
                $config
            )
        );
        $batchInsert = $batchInsert->chunk(300);
        foreach ($batchInsert as $payloads) {
            CookieProductionGrind::insert($payloads->toArray());
        }
    }

    /**
     * adjustment last grind
     *
     * @param double $realGrind
     * @param array $cookieProductionGrindData
     * @param integer $config
     * @return array
     */
    public function adjustmentLastGrind($realGrind, $lastGrindData, $config)
    {
        $grindTotal = collect($lastGrindData)->sum('total');
        $cek = str_contains($realGrind, '.');
        if ($cek) {
            //tambah atau kurangi sampai jumlahnya 108
            $allTotal = $config / 2;
            $finalTotal = $allTotal - $grindTotal;
            if ($finalTotal < 0) { //maka pengurangan
                $finalTotal = $finalTotal * -1;
                $total = 0;
                $totalGrindAll = $grindTotal;
                while ($total < ($finalTotal)) {
                    foreach ($lastGrindData as $key => $value) {
                        if ($totalGrindAll > $allTotal && $value['total'] != 0) {
                            $lastGrindData[$key]['total'] = $lastGrindData[$key]['total'] - 1;
                            $totalGrindAll = $totalGrindAll - 1;
                        }
                    }
                    $total++;
                }
            } else { //maka penambahan
                $total = 0;
                $totalGrindAll = $grindTotal;
                while ($total < ($finalTotal)) {
                    foreach ($lastGrindData as $key => $value) {
                        if ($totalGrindAll < $allTotal) {
                            $lastGrindData[$key]['total'] = $lastGrindData[$key]['total'] + 1;
                            $totalGrindAll = $totalGrindAll + 1;
                        }
                    }

                    $total++;
                }
            }
        } else { //216
            $allTotal = $config;
            $finalTotal = $config - $grindTotal;
            $total = 0;
            $totalGrindAll = $grindTotal;
            while ($total < ($finalTotal)) {
                foreach ($lastGrindData as $key => $value) {
                    if ($totalGrindAll < $allTotal) {
                        $lastGrindData[$key]['total'] = $lastGrindData[$key]['total'] + 1;
                        $totalGrindAll = $totalGrindAll + 1;
                    }
                }

                $total++;
            }
        }

        return $lastGrindData;
    }

    /**
     * Calculate Total Grinds
     *
     * @param integer $totalGrind
     * @param double $realGrind
     * @param date $date
     * @param integer $branchId
     * @param integer $totalQty
     * @return integer
     */
    public function adjustmentGrind($totalGrind, $realGrind, $date, $branchId, $config)
    {
        $cookieProductionIds = CookieProduction::where([
            'date' => $date,
            'branch_id' => $branchId,
        ])->where('total_target_after_remains', '>', 0)->orderBy('total_target_after_remains', 'DESC')->pluck('id');

        $grind = CookieProductionGrind::select('id', 'total')->whereIn('cookie_production_id', $cookieProductionIds)->where('grind', $totalGrind)->orderBy('total', 'DESC')->get();
        $grindTotal = $grind->sum('total');

        $cek = str_contains($realGrind, '.');
        if ($cek) {
            //tambah atau kurangi sampai jumlahnya 108
            $allTotal = $config / 2;
            $finalTotal = $allTotal - $grindTotal;
            if ($finalTotal < 0) { //maka pengurangan
                $finalTotal = $finalTotal * -1;
                $total = 0;
                $totalGrindAll = $grindTotal;
                while ($total < ($finalTotal)) {
                    foreach ($grind as $value) {
                        if ($totalGrindAll > $allTotal && $value->total != 0) {
                            $value->update([
                                'total' => $value->total - 1
                            ]);
                            $totalGrindAll = $totalGrindAll - 1;
                        }
                    }
                    $total++;
                }
            } else { //maka penambahan
                $total = 0;
                $totalGrindAll = $grindTotal;
                while ($total < ($finalTotal)) {
                    foreach ($grind as $value) {
                        if ($totalGrindAll < $allTotal) {
                            $value->update([
                                'total' => $value->total + 1
                            ]);
                            $totalGrindAll = $totalGrindAll + 1;
                        }
                    }

                    $total++;
                }
            }
        } else { //216
            $allTotal = $config;
            $finalTotal = $config - $grindTotal;
            $total = 0;
            $totalGrindAll = $grindTotal;
            while ($total < ($finalTotal)) {
                foreach ($grind as $value) {
                    if ($totalGrindAll < $allTotal) {
                        $value->update([
                            'total' => $value->total + 1
                        ]);
                        $totalGrindAll = $totalGrindAll + 1;
                    }
                }

                $total++;
            }
        }
    }
}
