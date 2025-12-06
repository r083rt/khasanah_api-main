<?php

namespace App\Services\Management;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class CustomerService
{
    /**
     * Get all
     */
    public function getAll($with = null, $has = null, $filter = [])
    {
        $key = 'customer';
        if (!Cache::has($key)) {
            $data = Customer::select('id', 'name', 'phone');

            if ($with) {
                $data = $data->with(['discounts', 'discounts.product:id,code,name,price,product_category_id']);
            }

            if ($filter) {
                foreach ($filter as $key => $value) {
                    if (is_array($value)) {
                        $data = $data->whereIn($key, $value);
                    } else {
                        $data = $data->where($key, $value);
                    }
                }
            }

            if ($has) {
                $data = $data->has($has);
            }

            $data = $data->orderBy('name')->get()->toArray();
            Cache::put($key, $data, 86400);
        } else {
            $data = Cache::get($key);
        }

        return $data;
    }
}
