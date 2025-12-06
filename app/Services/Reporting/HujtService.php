<?php

namespace App\Services\Reporting;

use App\Models\Purchasing\PoSupplier;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ReceivePoSupplier;
use App\Models\Purchasing\ReceivePoSupplierDetail;

class HujtService
{
    /**
     * Get all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // $po_suppliers = PoSupplier::select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])->with(['purchasingSupplier:*', 'poSupplierDetails:*,qty_received as old_received', 'poSupplierDetails.productRecipeUnit:id,name', 'poSupplierDetails.brand:id,name', 'poSupplierDetails.productIngredient:id,name,hpp,discount,price,real_price','receivePoSuppliers', 'receivePoSuppliers.receivePoSupplierDetails.productRecipeUnit:id,name', 'receivePoSuppliers.receivePoSupplierDetails.brand:id,name', 'receivePoSuppliers.receivePoSupplierDetails.productIngredient:id,name','returnPoSuppliers', 'returnPoSuppliers.returnPoSupplierDetails.productRecipeUnit:id,name', 'returnPoSuppliers.returnPoSupplierDetails.brand:id,name', 'returnPoSuppliers.returnPoSupplierDetails.productIngredient:id,name'])
        //                         // ->where('date', '>=', $startDate)
        //                         // ->where('date', '<', $endDate)
        //                         // ->whereRaw('(po_suppliers.date + INTERVAL purchasing_suppliers.day DAY) >= ?', [$startDate])
        //                         // ->whereRaw('(po_suppliers.date + INTERVAL purchasing_suppliers.day DAY) <= ?', [$endDate])
        //                         ->get();

        // $po_suppliers->transform(function ($po_supplier) {
        //     $po_supplier->due_date = date('Y-m-d', strtotime( $po_supplier->date . " " . $po_supplier->purchasingSupplier->day . " days"));
        //     return $po_supplier;
        // });

        // // return $po_suppliers;

        // $po_suppliers_due_range = [];

        // foreach($po_suppliers as $po_supplier) {
        //     if( $endDate >= $po_supplier->due_date && $startDate <= $po_supplier->due_date){
        //         array_push($po_suppliers_due_range, $po_supplier);
        //     }
        // }
        
        // // $po_suppliers_due_range = $po_suppliers->whereBetween('due_date', [$startDate, $endDate]);

        // $sum = 0;
        // foreach($po_suppliers_due_range as $po){
        //     $sum = 0;

        //     foreach($po->receivePoSuppliers as $receive){
        //         foreach($receive->receivePoSupplierDetails as $item){
        //             $sum += $item->real_price * $item->qty;
        //         }
        //     }

            
        //     $po['total_price'] = $sum;
        // }

        // return $po_suppliers_due_range;

        $receive_po_suppliers = ReceivePoSupplier::with(['receivePoSupplierDetails:*', 'poSupplier:*','poSupplier.purchasingSupplier:*'])->whereBetween('received_at', [$startDate, $endDate])->get();
        foreach($receive_po_suppliers as $receive_po_supplier){
            $sum = 0;
            foreach($receive_po_supplier->receivePoSupplierDetails as $detail) {
                $sum += $detail['qty'] * $detail['real_price'];
            }
            $receive_po_supplier['total'] = $sum;

            $day = $receive_po_supplier['poSupplier']['purchasingSupplier']['payment'] == "tempo" && $receive_po_supplier['poSupplier']['purchasingSupplier']['day'] ? $receive_po_supplier['poSupplier']['purchasingSupplier']['day'] : 0;

            $receive_po_supplier['date'] = date("Y-m-d", strtotime($receive_po_supplier['received_at'] . '+ ' . $day . ' day'));
        }
        return $receive_po_suppliers;
    }
}
