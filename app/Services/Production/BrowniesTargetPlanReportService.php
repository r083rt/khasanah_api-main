<?php

namespace App\Services\Production;

use App\Models\Distribution\PoOrderProductDetail;
use App\Models\Inventory\ProductStockLog;
use App\Models\OrderProduct;
use App\Models\Pos\Closing;
use App\Models\Pos\ClosingProduct;
use App\Models\Production\BrowniesTargetPlanBuffer;
use App\Models\Production\BrowniesTargetPlanBufferTarget;
use App\Models\Production\BrowniesTargetPlanSale;
use App\Models\ProductStock;
use App\Services\Inventory\ProductService;

class BrowniesTargetPlanReportService
{
    /**
     * Get Report
     *
     * @param string $date
     * @param int $branchId
     * @param date $day
     * @return collection
     */
    public function getReport($date, $branchId, $day)
    {
        if (!empty($branchId)) {
            $productService = app(ProductService::class);
            $products = $productService->getAllBrownies(false, $branchId, $day);
            foreach ($products as $value) {
                $average_target = $this->getTargetSale($date, $value->id, $branchId);
                $remains = $this->getRemains($date, $value->id, $branchId);
                $delivery = $this->getDelivery($date, $value->id, $branchId);
                $order = $this->getOrder($date, $value->id, $branchId);
                $minimum_stock = $this->getMinimumStock($date, $value->id, $branchId);
                $product = $this->getProduct($remains, $delivery);
                $estimation_product = $this->getEstimationProduct($average_target, $product);
                $po = $this->getPo($minimum_stock, $estimation_product, $order);
                $percentage = $this->getPercentage($estimation_product, $average_target);
                $sale = $this->getSale($date, $value->id, $branchId);
                $achievement_percentage = $this->getAchievementPercentage($sale, $average_target);

                $value->average_target = $average_target;
                $value->remains = $remains;
                $value->delivery = $delivery;
                $value->product = $product;
                $value->estimation_product = $estimation_product;
                $value->minimum_stock = $minimum_stock;
                $value->order = $order;
                $value->po = $po;
                $value->percentage = $percentage . '%';
                $value->sale = $sale;
                $value->achievement_percentage = $achievement_percentage . '%';
            }
        } else {
            $products = [];
        }

        return $products;
    }

    /**
     * Get Achievement Percentage
     *
     * @param int $sale
     * @param int $average_target
     * @return int
     */
    public function getAchievementPercentage($sale, $average_target)
    {
        if ($sale == 0 || $average_target == 0) {
            $achievement_percentage = 0;
        } else {
            $achievement_percentage = round($sale / $average_target * 100);
        }

        return $achievement_percentage;
    }

    /**
     * Get Percentage
     *
     * @param int $estimation_product
     * @param int $average_target
     * @return int
     */
    public function getPercentage($estimation_product, $average_target)
    {
        if ($estimation_product == 0 || $average_target == 0) {
            $percentage = 0;
        } else {
            $percentage = round($estimation_product / $average_target * 100);
        }

        return $percentage;
    }

    /**
     * Get Po
     *
     * @param int $minimum_stock
     * @param int $estimation_product
     * @param int $order
     * @return int
     */
    public function getPo($minimum_stock, $estimation_product, $order)
    {
        if ($estimation_product < 0) {
            $estimation_product = 0;
        }

        $po = $minimum_stock - $estimation_product + $order;
        if ($po < 0) {
            return $minimum_stock;
        }

        return $po;
    }

    /**
     * Get Estimation Product
     *
     * @param int $average_target
     * @param int $product
     * @return int
     */
    public function getEstimationProduct($average_target, $product)
    {
        $estimation_product = ($product - $average_target);
        if ($estimation_product < 0) {
            $estimation_product = 0;
        }

        return $estimation_product;
    }

    /**
     * Get Product
     *
     * @param int $remains
     * @param int $delivery
     * @return int
     */
    public function getProduct($remains, $delivery)
    {
        return ($remains + $delivery);
    }

    /**
     * Get Total PO
     *
     * @param string $date
     * @param int $branchId
     * @param int $productId
     * @return int
     */
    public function getTotalPo($date, $branchId, $productId)
    {
        $average_target = $this->getTargetSale($date, $productId, $branchId);
        $remains = $this->getRemains($date, $productId, $branchId);
        $delivery = $this->getDelivery($date, $productId, $branchId);
        $order = $this->getOrder($date, $productId, $branchId);
        $minimum_stock = $this->getMinimumStock($date, $productId, $branchId);
        $product = $this->getProduct($remains, $delivery);
        $estimation_product = $this->getEstimationProduct($average_target, $product);

        $po = $minimum_stock - $estimation_product + $order;
        if ($po < 0) {
            return $minimum_stock;
        }

        return $po;
    }
    /**
     * Get Target Sale
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getTargetSale($date, $productId, $branchId)
    {
        $day = date_to_day($date);
        $data = BrowniesTargetPlanSale::where([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'day' => $day,
        ])
        ->first();

        if ($data) {
            return $data->target;
        }

        return 0;
    }

    /**
     * Remains Closing
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getRemains($date, $productId, $branchId)
    {
        $date = date('Y-m-d', strtotime('-1 days', strtotime($date)));
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
     * Delivery
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getDelivery($date, $productId, $branchId)
    {
        $date = date('Y-m-d', strtotime('-1 days', strtotime($date)));
        $from = [
            'Po Brownis',
            'Po Brownis Toko',
            'Po Manual',
        ];

        $stock = ProductStockLog::where([
            'branch_id' => $branchId,
            'product_id' => $productId,
        ])
        ->whereIn('from', $from)
        ->whereDate('created_at', $date)
        ->sum('stock_after');

        return $stock;
    }

    /**
     * Minimum Stock
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getMinimumStock($date, $productId, $branchId)
    {
        $date = date('Y-m-d', strtotime('+1 days', strtotime($date)));
        $day = date_to_day($date);
        $dateExplode = explode('-', $date);

        $data = BrowniesTargetPlanBufferTarget::where([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'date_day' => $dateExplode[2],
            'date_month' => $dateExplode[1],
            'date_year' => $dateExplode[0],
        ])
        ->first();

        $buffer = 0;
        if ($data) {
            $buffer = $data->buffer;
        }

        $target = $this->getTargetSale($date, $productId, $branchId);

        if ($buffer == 0 || $target == 0) {
            $data = $target;
        } else {
            $value = $buffer * $target / 100;
            $data = round($target + $value);
        }

        $bufferProduksi = BrowniesTargetPlanBuffer::where([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'day' => $day,
        ])
        ->first();

        $buffer = 0;
        if ($bufferProduksi) {
            $buffer = $bufferProduksi->buffer;
        }

        if ($buffer == 0 || $data == 0) {
            $dataTotal = $data;
        } else {
            $value = $buffer * $data / 100;
            $dataTotal = round($data + $value);
        }

        return $dataTotal;
    }

    /**
     * Get Order
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getOrder($date, $productId, $branchId)
    {
        $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($date)));
        return (int) PoOrderProductDetail::where('product_id', $productId)
            ->whereHas('poOrderProduct', function ($query) use ($dateNext, $branchId) {
                $query = $query->where('type', 'order')->where('available_at', '>=', $dateNext)->where('branch_id', $branchId);
            })
            ->sum('qty');
    }

    /**
     * Get Sale
     *
     * @param string $date
     * @param int $productId
     * @param int $branchId
     * @return int
     */
    public function getSale($date, $productId, $branchId)
    {
        return (int) OrderProduct::select('qty')
        ->whereHas('orders', function ($query) use ($branchId, $date, $productId) {
            $query = $query->whereDate('created_at', $date)
                ->where('status_pickup', 'done')
                ->where('branch_id', $branchId)
                ->where('type', 'cashier')
                ->where('product_id', $productId);
        })
        ->sum('qty');
    }
}
