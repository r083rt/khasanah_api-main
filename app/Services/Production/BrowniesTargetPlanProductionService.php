<?php

namespace App\Services\Production;

use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanProduction;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;

class BrowniesTargetPlanProductionService
{
    /**
     * Get Production
     *
     * @param date $date
     * @param string $day
     * @return int
     */
    public function getProduction($date, $day)
    {
        $checkData = $this->checkData($date);
        if ($checkData->count() > 0) {
            foreach ($checkData as $value) {
                $value->product_code = $value->product ? $value->product->code : null;
                $value->product_name = $value->product ? $value->product->name : null;
            }

            return [
                'is_editable' => false,
                'data' => $checkData
            ];
        } else {
            $productService = new ProductService();
            $products = $productService->getAllBrownies(true, null, $day);
            foreach ($products as $value) {
                $total_po = $this->getTotalPo($date, $value->id);
                $barrel = $this->getBarrel($value->id);
                if ($barrel == 0 || $total_po == 0) {
                    $barrel_conversion = 0;
                } else {
                    $barrel_conversion = round($total_po / $barrel);
                }

                $value->total_po = $total_po;
                $value->barrel = $barrel;
                $value->barrel_conversion = $barrel_conversion;
                $value->edit_barrel = $barrel_conversion;
                $value->recipe_production = $barrel_conversion * $barrel;
            }

            return [
                'is_editable' => true,
                'data' => $products
            ];
        }
    }

    /**
     * Get Check data
     *
     * @param date $date
     * @return collection
     */
    public function checkData($date)
    {
        return BrowniesTargetPlanProduction::with(['product:id,name,code'])->where('date', $date)->get();
    }

    /**
     * Create data
     *
     * @param array $data
     * @return collection
     */
    public function create($data)
    {
        return BrowniesTargetPlanProduction::create($data);
    }

    /**
     * Get Total Po
     *
     * @param date $date
     * @param string $day
     * @return int
     */
    public function getTotalPo($date, $productId)
    {
        $browniesTargetPlanReportService = new BrowniesTargetPlanReportService();

        $branch = new BranchService();
        $branches = $branch->getAll(null, [
            'is_production' => 0
        ], true);
        $totalPo = 0;
        foreach ($branches as $value) {
            $total_po = $browniesTargetPlanReportService->getTotalPo($date, $value->id, $productId);
            $totalPo += $total_po;
        }

        return $totalPo;
    }

    /**
     * Get Barrel
     *
     * @param string $productId
     * @return int
     */
    public function getBarrel($productId)
    {
        $product = Product::select('mill_barrel')->find($productId);

        if ($product) {
            return $product->mill_barrel ?? 0;
        }

        return 0;
    }
}
