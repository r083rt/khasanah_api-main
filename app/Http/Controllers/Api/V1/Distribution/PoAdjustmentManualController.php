<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoManual;
use App\Models\Distribution\PoManualDetail;
use App\Models\Distribution\PoSjItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PoAdjustmentManualController extends Controller
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
    public function __construct(PoManual $model, StockService $stockService)
    {
        $this->middleware('permission:po-adjustment-manual.lihat|po-adjustment-manual.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:po-adjustment-manual.ubah', [
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
        $data = $this->model->where('status', 'product_incomplete')->with(['details', 'createdBy:id,name', 'details.product:id,name,code,product_unit_id', 'details.product.unit:id,name', 'details.ingredient:id,name,code', 'details.ingredient.unit', 'branch:id,name'])->branch()->search($request)->sort($request);
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBahan(Request $request)
    {
        $data = ProductIngredient::select('id', 'name', 'code')->search($request)->orderBy('name')->get();
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
        $model = $this->model->where('status', 'product_incomplete')->with(['details', 'createdBy:id,name', 'details.product:id,name,code,product_unit_id', 'details.product.unit:id,name', 'statusLogs', 'details.ingredient:id,name,code', 'details.ingredient.unit', 'statusLogs.createdBy:id,name', 'branch:id,name'])->branch()->findOrFail($id);
        $details = collect([]);
        foreach ($model->details as $value) {
            if ($value->product_id) {
                $poSjItem = PoSjItem::where('po_id', $id)->whereIn('type', ['po_manual_ingredient', 'po_manual_product'])->where('product_id', $value->product_id)->first();
            } else {
                $poSjItem = PoSjItem::where('po_id', $id)->whereIn('type', ['po_manual_ingredient', 'po_manual_product'])->where('product_ingredient_id', $value->product_ingredient_id)->first();
            }
            $qty_real = $poSjItem ? $poSjItem->qty_real : null;
            $qty_delivery = $poSjItem ? $poSjItem->qty_delivery : null;
            $value->qty_real = $qty_real;
            $value->qty = $qty_delivery;
            $is_added = $poSjItem ? $poSjItem->is_added : 0;
            if (($qty_real != $qty_delivery) || $is_added == 1) {
                $details->push($value);
            }
        }
        $model->details_update = $details;

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
            'products' => 'nullable|array',
            // 'products.*.product_id' => 'nullable|exists:products,id',
            // 'products.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            // 'products.*.qty_real' => 'nullable|integer',
        ]);

        $model = $this->model->findOrFail($id);
        if ($data['status'] == 'accepted') {
            $data['status'] = 'done';
        }

        $branch_id = Auth::user()->branch_id;

        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $branch_id) {
            $model->update($data);
            $model->statusLogs()->updateOrCreate(
                [
                    'status' => $data['status']
                ],
                [
                    'status' => $data['status']
                ],
            );

            $poSjItem = PoSjItem::select('branch_id', 'product_id', 'qty', 'qty_real', 'qty_delivery', 'po_sj_id', 'po_date', 'box_name', 'is_added')->whereIn('type', ['po_manual_product'])->where('po_id', $model->id)->get();
            // $box_name = null;
            // $po_sj_id = null;
            // $po_date = null;
            foreach ($poSjItem as $value) {
                // $box_name = $value->box_name;
                // $po_sj_id = $value->po_sj_id;
                // $po_date = $value->po_date;
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
                                'from' => 'Penyesuaian Po Manual',
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
                                'from' => 'Penyesuaian Po Manual',
                                'table_reference' => 'po_sj_items',
                                'table_id' => $value->id
                            ]);
                        }
                    }
                }
            }

            // if (!empty($data['products'])) {
            //     foreach ($data['products'] as $value) {
            //         if (isset($value['product_id'])) {
            //             $product = Product::find($value['product_id']);
            //             $po = PoSjItem::create([
            //                 'po_sj_id' => $po_sj_id,
            //                 'po_id' => $model->id,
            //                 'branch_id' => $branch_id,
            //                 'branch_receiver_id' => $branch_id,
            //                 'type' => 'po_manual_product',
            //                 'box_name' => $box_name,
            //                 'product_id' => $value['product_id'],
            //                 'code_item' => $product ? $product->code : '',
            //                 'name_item' => $product ? $product->name : '',
            //                 'qty' => $value['qty_real'],
            //                 'qty_real' =>$value['qty_real'],
            //                 'qty_delivery' =>$value['qty_real'],
            //                 'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
            //                 'unit_name_delivery' => $product ? $product->unit ? $product->unit->name : null : null,
            //                 'hpp' => $product ? $product->price_sale : null,
            //                 'received_date' => date('Y-m-d'),
            //                 'is_added' => 1,
            //                 'po_date' => $po_date
            //             ]);

            //             $stock = ProductStock::where('branch_id', $branch_id)->where('product_id', $value['product_id'])->first();
            //             if ($stock) {
            //                 $oldStock = $stock->stock;
            //                 $stock->update([
            //                     'stock' => $oldStock + $value['qty_real'],
            //                 ]);

            //                 $this->stockService->createStockLog([
            //                     'branch_id' => $branch_id,
            //                     'product_id' => $value['product_id'],
            //                     'stock' =>  $value['qty_real'],
            //                     'stock_old' => $oldStock,
            //                     'from' => 'Penyesuaian Po Manual',
            //                     'table_reference' => 'po_sj_items',
            //                     'table_id' => $po->id
            //                 ]);
            //             } else {
            //                 ProductStock::create([
            //                     'branch_id' => $branch_id,
            //                     'product_id' => $value['product_id'],
            //                     'stock' =>   $value['qty_real'],
            //                 ]);

            //                 $this->stockService->createStockLog([
            //                     'branch_id' => $branch_id,
            //                     'product_id' => $value['product_id'],
            //                     'stock' =>  $value['qty_real'],
            //                     'stock_old' => 0,
            //                     'from' => 'Penyesuaian Po Manual',
            //                     'table_reference' => 'po_sj_items',
            //                     'table_id' => $po->id
            //                 ]);
            //             }
            //         } else {
            //             $product = ProductIngredient::find($value['product_ingredient_id']);
            //             PoSjItem::create([
            //                 'po_sj_id' => $po_sj_id,
            //                 'po_id' => $model->id,
            //                 'branch_id' => $branch_id,
            //                 'branch_receiver_id' => $branch_id,
            //                 'type' => 'po_manual_ingredient',
            //                 'box_name' => $box_name,
            //                 'product_ingredient_id' => $value['product_ingredient_id'],
            //                 'code_item' => $product ? $product->code : '',
            //                 'name_item' => $product ? $product->name : '',
            //                 'qty' =>$value['qty_real'],
            //                 'qty_real' =>$value['qty_real'],
            //                 'qty_delivery' =>$value['qty_real'],
            //                 'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
            //                 'unit_name_delivery' => $product ? $product->unit ? $product->unit->name : null : null,
            //                 'hpp' => $product ? $product->hpp : null,
            //                 'received_date' => date('Y-m-d'),
            //                 'is_added' => 1,
            //                 'po_date' => $po_date
            //             ]);
            //         }
            //     }
            // }

            return $model;
        });

        return $this->response($data);
    }
}
