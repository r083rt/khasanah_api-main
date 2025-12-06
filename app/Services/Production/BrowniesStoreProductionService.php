<?php

namespace App\Services\Production;

use App\Models\Inventory\Packaging;
use App\Models\Product;
use App\Models\Production\BrowniesStoreProduction;
use App\Services\Inventory\ProductService;
use Illuminate\Support\Facades\DB;

class BrowniesStoreProductionService
{
    /**
     * Get Production
     *
     * @param date $date
     * @param string $day
     * @return int
     */
    public function getProduction($date, $day, $branchId)
    {
        $checkData = $this->checkData($date, $branchId);
        if ($checkData->count() > 0) {
            return [
                'is_editable' => false,
                'data' => $checkData
            ];
        } else {
            $productService = new ProductService();
            $products = $productService->getAllBrowniesStore($branchId, $day);
            $packaging = Packaging::with(['products'])->get();
            $alllPaketanProductId = [];
            foreach ($packaging as $value) {
                foreach ($value->products as $row) {
                    $alllPaketanProductId[] = $row->id;
                }
            }

            $datas = [];
            $paketanIds = [];
            foreach ($products as $value) {
                $total_po = $this->getTotalPo($date, $value->id, $branchId);
                if (!in_array($value->id, $alllPaketanProductId)) {
                    $pcs = $this->getPcs($value->id);
                    $grind = $this->getGrind($total_po, $pcs);
                    $datas[] = [
                        'master_packaging_id' => null,
                        'product_code' => $value->product_code,
                        'product_name' => $value->product_name,
                        'product_id' => $value->id,
                        'product_ids' => null,
                        'product_names' => null,
                        'total_po' => $total_po,
                        'grind' => $grind,
                        'pcs' => $pcs,
                        'recipe_production' => $this->reciveProduction($grind, $pcs)
                    ];
                } else {
                    $cek = DB::connection('mysql')->table('master_packaging_products')->where('product_id', $value->id)->get();
                    foreach ($cek as $row) {
                        if (isset($paketanIds[$row->master_packaging_id])) {
                            $productId = $paketanIds[$row->master_packaging_id];
                            array_push($productId, $row->product_id);
                            $paketanIds[$row->master_packaging_id] = $productId;
                        } else {
                            $paketanIds[$row->master_packaging_id] = [$row->product_id];
                        }
                    }
                }
            }

            foreach ($paketanIds as $key => $value) {
                $cek = $packaging->where('id', $key)->first();
                if ($cek) {
                    $totalPo = 0;
                    $productNames = null;
                    foreach ($value as $row) {
                        $totalAllPo = $this->getTotalPo($date, $row, $branchId);
                        $totalPo += $totalAllPo;

                        $product = $products->where('id', $row)->first();
                        if ($product) {
                            if ($productNames) {
                                $productNames = $productNames . ', ' . $product->name;
                            } else {
                                $productNames = $product->name;
                            }
                        }
                    }
                    $grind = $this->getGrind($totalAllPo, $cek->grinds);
                    $datas[] = [
                        'master_packaging_id' => $key,
                        'product_code' => null,
                        'product_name' => $cek->name,
                        'product_id' => null,
                        'product_ids' => $value,
                        'product_names' => $productNames,
                        'total_po' => $totalAllPo,
                        'grind' => $grind,
                        'pcs' => $cek->grinds,
                        'recipe_production' => $this->reciveProduction($grind, $cek->grinds)
                    ];
                }
            }

            usort($datas, function ($item1, $item2) {
                return $item1['product_name'] <=> $item2['product_name'];
            });

            return [
                'is_editable' => true,
                'data' => $datas
            ];
        }
    }

    /**
     * Get Check data
     *
     * @param date $date
     * @return collection
     */
    public function checkData($date, $branchId)
    {
        return BrowniesStoreProduction::where('date', $date)->where('branch_id', $branchId)->get();
    }

    /**
     * Create data
     *
     * @param array $data
     * @return collection
     */
    public function create($data)
    {
        return BrowniesStoreProduction::create($data);
    }

    /**
     * Get Total Po
     *
     * @param date $date
     * @param string $day
     * @return int
     */
    public function getTotalPo($date, $productId, $branchId)
    {
        $browniesTargetPlanReportService = new BrowniesTargetPlanReportService();
        return $browniesTargetPlanReportService->getTotalPo($date, $branchId, $productId);
    }

    /**
     * Get Grind
     *
     * @param integer $total_po
     * @param integer $totalGrind
     * @param integer $productId
     * @return int
     */
    public function getGrind($totalPo, $pcs)
    {
        if ($totalPo != 0 && $pcs != 0) {
            $result = $totalPo / $pcs;
            $explode = explode(".", $result);
            $start = $explode[0];
            if (isset($explode[1])) {
                $end = substr($explode[1], 0, 2);
                if ($end >= 55) {
                    return (int)$start + 1;
                } else {
                    return (int)$start;
                }
            } else {
                return (int)$start;
            }
        }

        return 0;
    }

    /**
     * Get Pcs
     *
     * @param integer $productId
     * @return int
     */
    public function getPcs($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            return $product->shop_roller ?? 0;
        }

        return 0;
    }

    /**
     * Recive production
     *
     * @param integer $grind
     * @param integer $pcs
     * @return int
     */
    public function reciveProduction($grind, $pcs)
    {
        return $grind * $pcs;
    }
}
