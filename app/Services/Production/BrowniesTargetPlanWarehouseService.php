<?php

namespace App\Services\Production;

use App\Services\Inventory\ProductService;

class BrowniesTargetPlanWarehouseService
{
    /**
     * Get Warehouse
     *
     * @param date $date
     * @param int $branchId
     * @return collection
     */
    public function getWarehouse($date, $branchId, $productId = null)
    {
        $day = date_to_day($date);
        $productService = new ProductService();
        $browniesTargetPlanReportService = new BrowniesTargetPlanReportService();

        $products = $productService->getAllBrownies(false, $branchId, $day, $productId);
        foreach ($products as $value) {
            $average_target = $browniesTargetPlanReportService->getTargetSale($date, $value->id, $branchId);
            $remains = $browniesTargetPlanReportService->getRemains($date, $value->id, $branchId);
            $delivery = $browniesTargetPlanReportService->getDelivery($date, $value->id, $branchId);
            $order = $browniesTargetPlanReportService->getOrder($date, $value->id, $branchId);
            $minimum_stock = $browniesTargetPlanReportService->getMinimumStock($date, $value->id, $branchId);
            $product = $browniesTargetPlanReportService->getProduct($remains, $delivery);
            $estimation_product = $browniesTargetPlanReportService->getEstimationProduct($average_target, $product);
            $po = $browniesTargetPlanReportService->getPo($minimum_stock, $estimation_product, $order);
            $percentage = $browniesTargetPlanReportService->getPercentage($estimation_product, $average_target);

            $value->estimation_product = $estimation_product;
            $value->minimum_stock = $minimum_stock;
            $value->order = $order;
            $value->po = $po;
            $value->percentage = $percentage . '%';
            $value->total = $po;
        }

        return $products;
    }
}
