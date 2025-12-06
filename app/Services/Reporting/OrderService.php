<?php

namespace App\Services\Reporting;

use App\Models\Order;

class OrderService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $data = Order::select('id', 'branch_id', 'product_category_id', 'customer_name', 'note', 'created_at', 'date_pickup', 'received_date', 'created_by', 'status_pickup', 'status_payment')
            ->order()
            ->with(['branch:id,name', 'category:id,name', 'createdBy:id,name', 'products:id,order_id,product_name,qty'])
            ->where('date_pickup', '>=', $startDate)
            ->where('date_pickup', '<=', $endDate);

        $data = $data->get();
        foreach ($data as $value) {
            $products = [];
            foreach ($value->products as $row) {
                $products[] = $row->product_name . ' (' . $row->qty . ')';
            }
            $value->product_name = implode(', ', $products);
        }

        return $data;
    }
}
