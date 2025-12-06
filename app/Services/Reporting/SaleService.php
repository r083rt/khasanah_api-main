<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderProduct;

class SaleService
{
    /**
     * Get sale service all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;
        $customer = $request->customer_id;
        $territory = $request->territory_id;

        if ($territory) {
            if ($branch) {
                $branchIds = [$branch];
            } else {
                $branchIds = Branch::select('id')->where('territory_id', $territory)->pluck('id');
            }
        } else {
            $branchIds = null;
        }

        $data = OrderProduct::with(['orders:id,branch_id,payment_name,created_at,customer_name,type,created_by', 'orders.branch:id,name', 'orders.createdBy:id,name', 'products.category:id,name'])
        ->whereHas('orders', function ($query) use ($startDate, $customer, $endDate, $branchIds) {
            $query = $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->where('type', 'cashier')->where('status', '!=', 'canceled');
            if ($branchIds) {
                $query = $query->whereIn('branch_id', $branchIds);
            }
            if ($customer) {
                $query = $query->where('customer_id', $customer);
            }
        })
        ->get();

        $datas = [];
        foreach ($data as $value) {
            $datas[] = [
                'customer_name' => $value->orders ? $value->orders->customer_name : null,
                'created_by' => $value->orders ? $value->orders->createdBy ? $value->orders->createdBy->name : null : null,
                'total_item' => $value->qty,
                'netto' => $value->total_price,
                'total_discount' => $value->discount,
                'total_price' => $value->product_price * $value->qty,
                'code' => $value->product_code,
                'product_name' => $value->product_name,
                'product_hpp' => $value->products ? $value->products->first() ? $value->products->first()->price_sale * $value->qty : null : null,
                'qty' => $value->qty,
                'created_at' => $value->orders ? $value->orders->created_at : null,
                'branch_name' => $value->orders ? $value->orders->branch ? $value->orders->branch->name : null : null,
                'product_category' => $value->products ? $value->products->first() ? $value->products->first()->category ? $value->products->first()->category ? $value->products->first()->category->name : null : null : null : null,
                'note' => 'Penjualan'
            ];
        }

        $data = OrderProduct::with(['orders:id,branch_id,payment_name,created_at,customer_name,type,received_date,created_by', 'orders.branch:id,name', 'orders.createdBy:id,name', 'products.category:id,name'])
        ->whereHas('orders', function ($query) use ($startDate, $customer, $endDate, $branchIds) {
            $query = $query->whereDate('received_date', '>=', $startDate)->whereDate('received_date', '<=', $endDate)->where('status_pickup', 'done')->where('type', 'order')->where('status', '!=', 'canceled');
            if ($branchIds) {
                $query = $query->whereIn('branch_id', $branchIds);
            }
            if ($customer) {
                $query = $query->where('customer_id', $customer);
            }
        })
        ->get();

        foreach ($data as $value) {
            $datas[] = [
                'customer_name' => $value->orders ? $value->orders->customer_name : null,
                'created_by' => $value->orders ? $value->orders->createdBy ? $value->orders->createdBy->name : null : null,
                'total_item' => $value->qty,
                'netto' => $value->total_price,
                'total_discount' => $value->discount,
                'total_price' => $value->product_price * $value->qty,
                'code' => $value->product_code,
                'product_name' => $value->product_name,
                'product_hpp' => $value->products ? $value->products->first() ? $value->products->first()->price_sale * $value->qty : null : null,
                'qty' => $value->qty,
                'created_at' => $value->orders ? $value->orders->received_date : null,
                'branch_name' => $value->orders ? $value->orders->branch ? $value->orders->branch->name : null : null,
                'product_category' => $value->products ? $value->products->first() ? $value->products->first()->category ? $value->products->first()->category ? $value->products->first()->category->name : null : null : null : null,
                'note' => 'Pesanan'
            ];
        }

        return $datas;
    }
}
