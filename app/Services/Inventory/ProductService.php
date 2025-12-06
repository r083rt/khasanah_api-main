<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanProduct;
use App\Models\Production\CookieProduct;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * Brownies Get product id
     */
    public function getProductIds($branchId = null, $day = null)
    {
        return BrowniesTargetPlanProduct::select('product_id')->where([
            'branch_id' => $branchId,
            'day' => $day,
            'is_production' => 1,
        ])
        ->pluck('product_id');
    }

    /**
     * Cookie Get product id
     */
    public function getProductCookieIds($branchId = null, $day = null)
    {
        return CookieProduct::select('product_id')->where([
            'branch_id' => $branchId,
            'day' => $day,
            'is_production' => 1,
        ])
        ->pluck('product_id');
    }

    /**
     * Get list product production
     */
    public function getProductProduction($branchId = null, $day = null)
    {
        $productIds = $this->getProductIds($branchId, $day);
        return Product::select('id', 'name')->whereIn('id', $productIds)->whereIn('product_category_id', config('production.brownies_target_product_category_id'))->available()->orderBy('name')->get();
    }

    /**
     * Get all Brownies
     */
    public function getAllBrownies($all = false, $branchId = null, $day = null, $productId = null)
    {
        $data = Product::select('id', 'code', 'name', 'barcode')
            ->with('stocks:id,stock,product_id')
            ->whereIn('product_category_id', config('production.brownies_target_bolu_cake_id'));

        if ($productId) {
            $data = $data->where('id', $productId);
        } else {
            if ($branchId) {
                $productIds = $this->getProductIds($branchId, $day);
                $data = $data->available($all, $branchId)->whereIn('id', $productIds);
            }
        }

        $data = $data->orderBy('name')->get();

        foreach ($data as $value) {
            $value->product_code = $value->code;
            $value->product_name = $value->name;
        }

        return $data;
    }

    /**
     * Get all Brownies Store
     */
    public function getAllBrowniesStore($branchId, $day)
    {
        $data = Product::select('id', 'code', 'name', 'barcode')
            ->with('stocks:id,stock,product_id')
            ->whereIn('product_category_id', config('production.brownies_target_bolu_cake_id'));

        $productIds = $this->getProductIds($branchId, $day);
        $data = $data->available(false, $branchId)->whereIn('id', $productIds);

        $data = $data->orderBy('name')->get();

        foreach ($data as $value) {
            $value->product_code = $value->code;
            $value->product_name = $value->name;
        }

        return $data;
    }

    /**
     * Get all Cookie
     */
    public function getAllCookie($all = false, $branchId = null, $day = null)
    {
        $data = Product::select('id', 'code', 'name')
            ->with('stocks:id,stock,product_id')
            ->whereIn('product_category_id', config('production.cookie_categories'));

        if ($branchId) {
            $productIds = $this->getProductCookieIds($branchId, $day);
            $data = $data->available($all, $branchId)->whereIn('id', $productIds);
        }

        $data = $data->orderBy('name')->get();

        foreach ($data as $value) {
            $value->product_code = $value->code;
            $value->product_name = $value->name;
        }

        return $data;
    }

    /**
     * getAllWhereHaveRecipe
     */
    public function getAllWhereHaveRecipe()
    {
        $data = Product::select('id', 'code', 'name')->whereHas('recipes');

        $data = $data->orderBy('name')->get();

        foreach ($data as $value) {
            $value->product_code = $value->code;
            $value->product_name = $value->name;
        }

        return $data;
    }

    /**
     * Get all
     */
    public function getAll($all = false, $branchId = null, $selects = '*')
    {
        $key = 'product-' . $all . '-' . $branchId;
        if (!Cache::has($key)) {
            $data = Product::select($selects)->available($all, $branchId)
                ->orderBy('name')
                ->get()
                ->toArray();
            Cache::put($key, $data, 86400);
        } else {
            $data = Cache::get($key);
        }

        return $data;
    }
}
