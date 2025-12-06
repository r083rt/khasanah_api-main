<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchasing\PoSupplier as PurchasingPoSupplier;
use App\Models\Purchasing\PoSupplierDetail;
use App\Models\Purchasing\PurchasingSupplier;
use App\Models\Purchasing\ReceivePoSupplier;
use App\Models\Purchasing\ReceivePoSupplierDetail;
use App\Models\Purchasing\ReturnSupplier;
use App\Models\Purchasing\ReturnSuppliersDetail;
use App\Models\ProductIngredient;
use App\Models\Product;
use App\Services\Inventory\IngredientStockService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoReturnController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;
    protected $branchService;
    protected $ingredientStockService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ReturnSupplier $model, BranchService $branchService, IngredientStockService $ingredientStockService)
    {
        $this->middleware('permission:po-return.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:po-return.tambah', [
            'only' => ['store']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
        $this->ingredientStockService = $ingredientStockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model->with(['purchasingSupplier:id,name', 'poSupplier:id,po_number'])->search($request)->sort($request);

        if ($start_date = $request->start_date ) {
            if ($end_date = $request->end_date) {
                $data = $data->where('return_at', '>=', $start_date)->where('return_at', '<=', $end_date);
            }
        }

        if ($purchasing_supplier_id = $request->purchasing_supplier_id) {
            $data = $data->where('purchasing_supplier_id', $purchasing_supplier_id);
        }
        if ($status_delivery = $request->status_delivery) {
            $data = $data->where('status_delivery', $status_delivery);
        }

        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSupplier(Request $request)
    {
        $datas = PurchasingSupplier::select('id', 'name')->search($request)->orderBy('name')->get();

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $datas = ProductIngredient::select(['id', 'id as product_ingredient_id', 'name','code', 'barcode', 'product_recipe_unit_id'])->with(['unit:id,name'])->search($request)->orderBy('name')->get();

        foreach($datas as $data) {
            $data->unit_name  = isset($data->unit) ? $data->unit->name : '';
        }

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listPoSuppliers(Request $request)
    {
        $datas = PurchasingPoSupplier::select('id', 'po_number')->where('purchasing_supplier_id', $request->search)->orderBy('po_number')->get();

        return $this->response($datas);
    }

    public function listPoSupplierProducts(Request $request)
    {
        $datas = PoSupplierDetail::select([
            'id',
            'product_ingredient_id',
            'product_recipe_unit_id',
            'brand_id',
            'qty',
            'barcode',
            'po_supplier_id',
        ])->with([
            'productIngredient:id,name,code',
            'productRecipeUnit:id,name',
            'brand:id,name',
        ])
        ->where('po_supplier_id', $request->search)
        ->get();

        foreach ($datas as $detail) {
            $detail->name = $detail->productIngredient->name;
            $detail->code = $detail->productIngredient->code;
            $detail->unit_name = $detail->productRecipeUnit->name;
        }

        return $this->response($datas);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->select(['id', 'return_number', 'return_at', 'supplier_id', 'po_supplier_id','note'])->with(['returnSuppliersDetails','returnSuppliersDetails.productIngredient', 'returnSuppliersDetails.productRecipeUnit', 'poSupplier:id,po_number', 'purchasingSupplier:id,name'])->findOrFail($id);

        foreach ($model->returnSuppliersDetails as $detail) {
            $detail->productId = $detail->productIngredient->id;
            $detail->productName = $detail->productIngredient->name;
            $detail->unit_name = $detail->productRecipeUnit->name;
        }

        return $this->response($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function scanBarcode($id, $barcode)
    {
        $model = PoSupplierDetail::select([
            'id',
            'product_ingredient_id',
            'product_recipe_unit_id',
            'brand_id',
            'qty',
            'barcode'
        ])->with([
            'productIngredient:id,name',
            'productRecipeUnit:id,name',
            'brand:id,name',
        ])
        ->where('barcode', $barcode)
        ->whereHas('poSupplier', function($query) use ($id) {
            $query->where('po_number', $id);
        })
        ->firstOrFail();

        return $this->response($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'supplierId' => 'required|integer',
            'poSupplierId' => 'nullable',
            'productIngredients.*' => 'required|array',
            'productIngredients.*.productId' => 'required',
            'productIngredients.*.qty' => 'required|integer',
            'note' => 'nullable'
        ]);

        if($data['poSupplierId']){
            foreach ($data['productIngredients'] as $value) {
                $cek = PoSupplierDetail::where('product_ingredient_id', $value['productId'])->where('po_supplier_id', $data['poSupplierId'])->first();
                if ($cek) {
                    if (((int) $cek->qty_received) < (int) $value['qty']) {
                        return $this->response('Ada jumlah barang yang melebihi total jumlah PO', 'error', 422);
                    }
                } else {
                    return $this->response('Ada barcode yang tidak valid', 'error', 422);
                }
            }
        }
        

        $data = DB::connection('mysql')->transaction(function () use ($data) {

            $return_po = new ReturnSupplier();
            $return_data = $return_po->create([
                'po_supplier_id' => $data['poSupplierId'],
                'return_number' => return_number_btb(),
                'supplier_id' => $data['supplierId'],
                'return_at' => date('Y-m-d'),
                'note' => $data['note']
            ]);

            foreach ($data['productIngredients'] as $value) {
                if($data['poSupplierId']){
                    $cek = PoSupplierDetail::where('product_ingredient_id', $value['productId'])->where('po_supplier_id', $data['poSupplierId'])->first();
                    
                    if ($cek) {
                        $cek->update([
                            'qty_received' => (int)$cek->qty_received - (int)$value['qty'],
                            'qty_returned' => $value['qty']
                        ]);

                        $this->ingredientStockService->createFromPoSupplier(
                            $cek->product_ingredient_id,
                            config('inventory.branch_kantor_pusat_id'),
                            $cek->product_recipe_unit_id,
                            $value['qty'] * (-1),
                            $cek->id
                        );

                        $return_po_details = new ReturnSuppliersDetail();
                        $return_po_details->create([
                            'return_supplier_id' => $return_data->id,
                            'product_ingredient_id' => $cek->product_ingredient_id,
                            'product_recipe_unit_id' => $cek->product_recipe_unit_id,
                            'brand_id' => $cek->brand_id,
                            'qty'=>$value['qty'],
                            'barcode'=>$cek->barcode,
                        ]);
                    }
                } else {
                    $productIngredient = ProductIngredient::select(['id', 'name','code', 'barcode', 'brand_id', 'product_recipe_unit_id'])->with(['unit:id,name'])->where('id', $value['productId'])->first();
                    $this->ingredientStockService->createFromPoSupplier(
                        $value['productId'],
                        config('inventory.branch_kantor_pusat_id'),
                        $productIngredient->product_recipe_unit_id,
                        $value['qty'] * (-1),
                        0
                    );

                    $return_po_details = new ReturnSuppliersDetail();
                    $return_po_details->create([
                        'return_supplier_id' => $return_data->id,
                        'product_ingredient_id' => $value['productId'],
                        'product_recipe_unit_id' => $productIngredient->product_recipe_unit_id,
                        'brand_id' => $productIngredient->brand_id,
                        'qty'=>$value['qty'],
                        'barcode'=>$productIngredient->barcode,
                    ]);
                }
                
            }

            return true;
        });

        return $this->response(true);
    }

    public function all(Request $request)
    {
        $model = $this->model->select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])->with(['purchasingSupplier:id,name,address', 'poSupplierDetails', 'poSupplierDetails.productRecipeUnit:id,name', 'poSupplierDetails.brand:id,name', 'poSupplierDetails.productIngredient:id,name'])->get();
        return $this->response($model);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validate($request, [
            'supplierId' => 'required|integer',
            'poSupplierId' => 'nullable',
            'productIngredients.*' => 'required|array',
            'productIngredients.*.productId' => 'required',
            'productIngredients.*.qty' => 'required|integer',
            'note' => 'nullable'
        ]);

        $model = $this->model->findOrFail($id);

        if($data['poSupplierId']){
            foreach ($data['productIngredients'] as $value) {
                $cek = PoSupplierDetail::where('product_ingredient_id', $value['productId'])->where('po_supplier_id', $data['poSupplierId'])->first();
                if ($cek) {
                    if (((int) $cek->qty_received + (int) $cek->qty_returned) < (int) $value['qty']) {
                        return $this->response('Ada jumlah barang yang melebihi total jumlah PO', 'error', 422);
                    }
                } else {
                    return $this->response('Ada barcode yang tidak valid', 'error', 422);
                }
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $id) {

            $model = $this->model->findOrFail($id);
            
            $model->update([
                'supplier_id' => $data['supplierId'],
                'po_supplier_id' => $data['poSupplierId'],
                'note' => $data['note']
            ]);

            $return_old_data = ReturnSuppliersDetail::where('return_supplier_id', $id)->get();

            foreach ($return_old_data as $old_detail) {
                $this->ingredientStockService->createFromPoSupplier(
                    $old_detail['product_ingredient_id'],
                    config('inventory.branch_kantor_pusat_id'),
                    $old_detail['product_recipe_unit_id'],
                    $old_detail['qty'],
                    0
                );
            }

            $return_old_data = ReturnSuppliersDetail::where('return_supplier_id', $id)->delete();

            foreach ($data['productIngredients'] as $value) {
                if($data['poSupplierId']){
                    $cek = PoSupplierDetail::where('product_ingredient_id', $value['productId'])->where('po_supplier_id', $data['poSupplierId'])->first();
                    
                    if ($cek) {
                        $cek->update([
                            'qty_received' => (int)$cek->qty_received + (int)$cek->qty_returned - (int)$value['qty'],
                            'qty_returned' => $value['qty']
                        ]);

                        $this->ingredientStockService->createFromPoSupplier(
                            $cek->product_ingredient_id,
                            config('inventory.branch_kantor_pusat_id'),
                            $cek->product_recipe_unit_id,
                            $value['qty'] * (-1),
                            $cek->id
                        );

                        $return_po_details = new ReturnSuppliersDetail();
                        $return_po_details->create([
                            'return_supplier_id' => $model->id,
                            'product_ingredient_id' => $cek->product_ingredient_id,
                            'product_recipe_unit_id' => $cek->product_recipe_unit_id,
                            'brand_id' => $cek->brand_id,
                            'qty'=>$value['qty'],
                            'barcode'=>$cek->barcode,
                        ]);
                    }
                } else {
                    $productIngredient = ProductIngredient::select(['id', 'name','code', 'barcode', 'brand_id', 'product_recipe_unit_id'])->with(['unit:id,name'])->where('id', $value['productId'])->first();
                    $this->ingredientStockService->createFromPoSupplier(
                        $value['productId'],
                        config('inventory.branch_kantor_pusat_id'),
                        $productIngredient->product_recipe_unit_id,
                        $value['qty'] * (-1),
                        0
                    );

                    $return_po_details = new ReturnSuppliersDetail();
                    $return_po_details->create([
                        'return_supplier_id' => $model->id,
                        'product_ingredient_id' => $value['productId'],
                        'product_recipe_unit_id' => $productIngredient->product_recipe_unit_id,
                        'brand_id' => $productIngredient->brand_id,
                        'qty'=>$value['qty'],
                        'barcode'=>$productIngredient->barcode,
                    ]);
                }
                
            }

            return $model;
        });

        return $this->response($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'required|array',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $delete = $this->model->whereIn('id', $data['id'])->delete();
            $detail_delete = ReturnSuppliersDetail::whereIn('return_supplier_id', $data['id'])->delete();

            return $delete && $detail_delete;
        });

        return $this->response($data ? true : false);
    }
}
