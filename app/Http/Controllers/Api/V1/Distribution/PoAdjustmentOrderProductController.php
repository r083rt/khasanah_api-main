<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoSjItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;

class PoAdjustmentOrderProductController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;
    protected $stockService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(PoOrderProduct $model, StockService $stockService)
    {
        $this->middleware('permission:po-adjustment-produk.lihat|po-adjustment-produk.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:po-adjustment-produk.ubah', [
            'only' => ['approval']
        ]);
        $this->model = $model;
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model->where('status', 'product_incomplete')->order()->with(['details', 'createdBy:id,name', 'details.product:id,name,code,product_unit_id', 'details.product.unit:id,name', 'branch:id,name'])->branch()->available()->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct()
    {
        $data = Product::select('id', 'code', 'name', 'price')->whereIn('product_category_id', [1, 4])->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->where('status', 'product_incomplete')->with(['details', 'createdBy:id,name', 'details.product:id,name,code,product_unit_id', 'details.product.unit:id,name', 'statusLogs', 'statusLogs.createdBy:id,name', 'branch:id,name'])->branch()->available()->findOrFail($id);
        foreach ($model->details as $key => $value) {
            $poSjItem = PoSjItem::select('qty_real')->where('po_id', $id)->where('type', 'po_order_product')->where('product_id', $value->product_id)->first();
            $value->qty_real = $poSjItem ? $poSjItem->qty_real : null;
        }

        return $this->response($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approval(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:accepted,rejected',
        ]);

        $model = $this->model->findOrFail($id);
        if ($data['status'] == 'accepted') {
            $data['status'] = 'done';
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->statusLogs()->updateOrCreate(
                [
                    'status' => $data['status']
                ],
                [
                    'status' => $data['status']
                ],
            );

            $poSjItem = PoSjItem::select('branch_id', 'product_id', 'qty', 'qty_real', 'qty_delivery', 'po_sj_id', 'po_date', 'box_name', 'is_added')->whereIn('type', ['po_order_product'])->where('po_id', $model->id)->get();
            foreach ($poSjItem as $value) {
                $diff = $value->qty_delivery - $value->qty_real;
                if (($data['status'] == 'done' && $diff != 0) || $value['is_added'] == 1) {
                    $qty =  $value->qty_real;
                    $product = Product::select('unit_value')->find($value->product_id);
                    if ($product) {
                        $qty = Product::getTotalUnit($qty, $product->unit_value);
                        $stock = ProductStock::where('branch_id', $value->branch_id)->where('product_id', $value->product_id)->first();
                        if ($stock) {
                            $oldStock = $stock->stock;
                            $stock->update([
                                'stock' => $oldStock + $qty
                            ]);

                            $this->stockService->createStockLog([
                                'branch_id' => $value->branch_id,
                                'product_id' =>  $value->product_id,
                                'stock' => $qty,
                                'stock_old' => $oldStock,
                                'from' => 'Penyesuaian Po Pesanan',
                                'table_reference' => 'po_sj_items',
                                'table_id' => $value->id
                            ]);
                        } else {
                            ProductStock::create([
                                'branch_id' =>  $value->branch_id,
                                'product_id' =>  $value->product_id,
                                'stock' =>  $qty
                            ]);

                            $this->stockService->createStockLog([
                                'branch_id' => $value->branch_id,
                                'product_id' => $value->product_id,
                                'stock' => $qty,
                                'stock_old' => 0,
                                'from' => 'Penyesuaian Po Pesanan',
                                'table_reference' => 'po_sj_items',
                                'table_id' => $value->id
                            ]);
                        }
                    }
                }
            }

            return $model;
        });

        return $this->response($data);
    }
}
