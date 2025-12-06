<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchasing\PoSupplier as PurchasingPoSupplier;
use App\Models\Purchasing\PoSupplierDetail;
use App\Models\Purchasing\PurchasingSupplier;
use App\Models\Purchasing\ReceivePoSupplier;
use App\Models\Purchasing\ReceivePoSupplierDetail;
use App\Models\Purchasing\ReturnPoSupplier;
use App\Models\Purchasing\ReturnPoSupplierDetail;
use App\Models\ProductIngredient;
use App\Services\Inventory\IngredientStockService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PoReceiveController extends Controller
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
    public function __construct(PurchasingPoSupplier $model, BranchService $branchService, IngredientStockService $ingredientStockService)
    {
        $this->middleware('permission:purchasing-penerimaan-barang.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:purchasing-penerimaan-barang.tambah', [
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
        $data = $this->model->with(['purchasingSupplier:id,name'])->search($request)->sort($request);

        if ($start_date = $request->start_date) {
            if ($end_date = $request->end_date) {
                $data = $data->where('date', '>=', $start_date)->where('date', '<=', $end_date);
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
        $datas = PurchasingSupplier::select('id', 'name')->get();

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
        $po = PurchasingPoSupplier::find($id);
        $status = $po->status_delivery;

        if ($status === "received") {
            $model = $this->model->select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])
                ->with([
                    'purchasingSupplier:id,name,address,discount',
                    'poSupplierDetails:*,qty_received as old_received',
                    'poSupplierDetails.productRecipeUnit:id,name',
                    'poSupplierDetails.brand:id,name',
                    'poSupplierDetails.productIngredient:id,name,hpp,discount,price,real_price',
                    'receivePoSuppliers',
                    'receivePoSuppliers.receivePoSupplierDetails.productRecipeUnit:id,name',
                    'receivePoSuppliers.receivePoSupplierDetails.brand:id,name',
                    'receivePoSuppliers.receivePoSupplierDetails.productIngredient:id,name',
                    'returnPoSuppliers',
                    'returnPoSuppliers.returnPoSupplierDetails.productRecipeUnit:id,name',
                    'returnPoSuppliers.returnPoSupplierDetails.brand:id,name',
                    'returnPoSuppliers.returnPoSupplierDetails.productIngredient:id,name'
                ])
                ->findOrFail($id);

            return $this->response($model);
        } else {
            $model = $this->model->select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])
                ->with([
                    'purchasingSupplier:id,name,address,discount',
                    'poSupplierDetails' => function ($query) {
                        // Mengganti null menjadi 0 untuk qty_received dan melakukan filter
                        $query->whereRaw('IFNULL(qty_received, 0) != qty')
                            ->selectRaw('*, (qty - IFNULL(qty_received, 0)) as qty, 0 as qty_received, qty_received as old_received');
                    },
                    'poSupplierDetails.productRecipeUnit:id,name',
                    'poSupplierDetails.brand:id,name',
                    'poSupplierDetails.productIngredient:id,name,hpp,discount,price,real_price',
                    'receivePoSuppliers',
                    'receivePoSuppliers.receivePoSupplierDetails.productRecipeUnit:id,name',
                    'receivePoSuppliers.receivePoSupplierDetails.brand:id,name',
                    'receivePoSuppliers.receivePoSupplierDetails.productIngredient:id,name',
                    'returnPoSuppliers',
                    'returnPoSuppliers.returnPoSupplierDetails.productRecipeUnit:id,name',
                    'returnPoSuppliers.returnPoSupplierDetails.brand:id,name',
                    'returnPoSuppliers.returnPoSupplierDetails.productIngredient:id,name'
                ])
                ->findOrFail($id);

            return $this->response($model);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function scanBarcode($id, $barcode)
    {
        Log::info('Params:', [
            'id' => $id,
            'barcode' => $barcode
        ]);
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
            ->whereHas('poSupplier', function ($query) use ($id) {
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


    public function all(Request $request)
    {
        $model = $this->model->select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])->with(['purchasingSupplier:id,name,address', 'poSupplierDetails', 'poSupplierDetails.productRecipeUnit:id,name', 'poSupplierDetails.brand:id,name', 'poSupplierDetails.productIngredient:id,name,product_recipe_unit_id', 'poSupplierDetails.productIngredient.unit'])->get();

        foreach ($model as $value) {
            foreach ($value->poSupplierDetails as $detail) {
                $unit = $detail->product_recipe_unit_id;

                $unit_data = $detail->productIngredient->unit;

                if ($unit_data) {
                    if ($unit_data->parent_id_4 && $unit_data->parent_id_4 == $unit) {

                        $conversion = $unit_data->parent_id_2_conversion * $unit_data->parent_id_3_conversion * $unit_data->parent_id_4_conversion;
                    } elseif ($unit_data->parent_id_3 && $unit_data->parent_id_3 == $unit) {

                        $conversion = $unit_data->parent_id_2_conversion * $unit_data->parent_id_3_conversion;
                    } elseif ($unit_data->parent_id_2 && $unit_data->parent_id_2 == $unit) {

                        $conversion = $unit_data->parent_id_2_conversion;
                    } else {

                        $conversion = 1;
                    }
                } else {
                    $conversion = 1;
                }

                $detail->conversion = $conversion;
                $detail->small_unit = $unit_data->name;
            }
        }

        return $this->response($model);
    }


    public function store(Request $request, $id)
    {
        $data = $this->validate($request, [
            'product_ingredients.*' => 'required|array',
            'product_ingredients.*.barcode' => 'required',
            'product_ingredients.*.qty_received' => 'required|integer',
            'product_ingredients.*.qty_bonus' => 'nullable|integer',
            'product_ingredients.*.note' => 'nullable',
            'type' => 'required|string',
            'discount' => 'nullable|numeric'
        ]);

        $model = PurchasingPoSupplier::findOrFail($id);

        if ($data['type'] == "add") {
            foreach ($data['product_ingredients'] as $value) {
                $cek = PoSupplierDetail::where('barcode', $value['barcode'])->where('po_supplier_id', $id)->first();
                if ($cek) {
                    if (((int) $cek->qty - (int) $cek->qty_received) < (int) $value['qty_received']) {
                        return $this->response('Ada jumlah barang yang melebihi total jumlah PO', 'error', 422);
                    }
                }
            }

            $data = DB::connection('mysql')->transaction(function () use ($data, $model, $id) {

                $check_receive_po = ReceivePoSupplier::where('po_supplier_id', $id)->orderBy('created_at', 'desc')->first();

                if ($check_receive_po) {
                    $old_rg = $check_receive_po->rg_number;
                    $pieces = explode("-", $old_rg);
                    $rg_number = isset($pieces[2]) ? $pieces[0] . "-" . $pieces[1] . '-' . ((int)$pieces[2] + 1) : $old_rg . '-' . '1';
                } else {
                    $rg_number = receipt_number_btb();
                }

                $receive_po = new ReceivePoSupplier();
                $receive_data = $receive_po->create([
                    'po_supplier_id' => $id,
                    'rg_number' => $rg_number,
                    'received_by' => Auth::id(),
                    'received_at' => date('Y-m-d')
                ]);

                $partial = false;

                foreach ($data['product_ingredients'] as $value) {

                    $cek = PoSupplierDetail::where('barcode', $value['barcode'])->where('po_supplier_id', $id)->first();
                    // if( $value['product_ingredient']['real_price'] > 0 ){
                    //     $productingredient = ProductIngredient::where('id', $value['product_ingredient']['id'])->first();
                    //     $productingredient->real_price = $value['product_ingredient']['real_price'];
                    //     $productingredient->update();
                    // }  

                    // $ingredient = ProductIngredient::where('barcode', $value['barcode'])->first();
                    $ingredient = ProductIngredient::where('id', $value['product_ingredient_id'])->first();
                    if ($cek) {
                        if ((int)$value['qty'] != (int)$value['qty_received']) {
                            $partial = true;
                        }
                        Log::info('Nilai variabel $partial setelah pemrosesan:', ['partial' => $partial, 'cek qty' => $cek->qty, 'param qty' => $value['qty'], 'param qty_received' => $value['qty_received']]);
                        $cek->update([
                            'qty_received' => (int)$cek->qty_received + (int)$value['qty_received'],
                            'qty_bonus' => (int)$cek->qty_bonus + (int)$value['qty_bonus'], // tambahan bonus
                            'received_by' => Auth::id(),
                            'note' => $value['note'],
                        ]);

                        // $finalQty = $value['qty_received'] + $value['qty_bonus'];
                        $this->ingredientStockService->createFromPoSupplier(
                            $cek->product_ingredient_id,
                            config('inventory.branch_kantor_pusat_id'),
                            $cek->product_recipe_unit_id,
                            $value['qty_received'],
                            $cek->id
                        );

                        $receive_po_details = new ReceivePoSupplierDetail();
                        $receive_po_details->create([
                            'receive_id' => $receive_data->id,
                            'product_ingredient_id' => $ingredient['product_ingredient_id'],
                            'product_recipe_unit_id' => $ingredient['product_recipe_unit_id'],
                            'brand_id' => $cek->brand_id,
                            'qty' => $value['qty_received'],
                            'bonus' => $value['qty_bonus'],
                            'barcode' => $cek->barcode,
                            'real_price' => $ingredient['real_price'] ? $ingredient['real_price'] : 0,
                            'price' => $ingredient['price'] ? $ingredient['price'] : 0,
                            'discount' => $ingredient['discount'] ? $ingredient['discount'] : 0
                        ]);
                    } else {

                        $poSuppDetails = new PoSupplierDetail();
                        $poSuppDetails->create([
                            'po_supplier_id' => $id,
                            'product_ingredient_id' => $ingredient['id'],
                            'product_recipe_unit_id' => $ingredient['product_recipe_unit_id'],
                            'brand_id' => $ingredient['brand_id'],
                            'barcode' => $ingredient['barcode'],
                            'qty' => 0,
                            'qty_received' => 0,
                            'qty_bonus' => $value['qty_bonus'], // tambahan bonus
                            'received_by' => Auth::id(),
                            'note' => 'bonus',
                        ]);

                        $receive_po_details = new ReceivePoSupplierDetail();
                        $receive_po_details->create([
                            'receive_id' => $receive_data->id,
                            'product_ingredient_id' => $ingredient['product_ingredient_id'],
                            'product_recipe_unit_id' => $ingredient['product_recipe_unit_id'],
                            'brand_id' => $ingredient['brand_id'],
                            'qty' => $value['qty_received'],
                            'bonus' => $value['qty_bonus'],
                            'barcode' => $ingredient['barcode'],
                            'real_price' => $ingredient['real_price'] ? $ingredient['real_price'] : 0,
                            'price' => $ingredient['price'] ? $ingredient['price'] : 0,
                            'discount' => $ingredient['discount'] ? $ingredient['discount'] : 0
                        ]);
                    }
                }
                $status_delivery = 'received';
                if ($partial == true) {
                    $status_delivery = 'partial';
                }
                Log::info('Status Delivery yang ditetapkan:', ['status_delivery' => $status_delivery]);
                return $model->update([
                    'receipt_number' => receipt_number_btb(),
                    'status_delivery' =>  $status_delivery,
                    'received_at' => date('Y-m-d'),
                ]);
            });
        } else {
            foreach ($data['product_ingredients'] as $value) {
                $cek = PoSupplierDetail::where('barcode', $value['barcode'])->where('po_supplier_id', $id)->first();
                if ($cek) {
                    if ((int) $cek->qty_received < (int) $value['qty_received']) {
                        return $this->response('Ada jumlah barang yang melebihi total jumlah PO', 'error', 422);
                    }
                } else {
                    return $this->response('Ada barcode yang tidak valid', 'error', 422);
                }
            }

            $data = DB::connection('mysql')->transaction(function () use ($data, $model, $id) {

                $return_po = new ReturnPoSupplier();
                $return_data = $return_po->create([
                    'po_supplier_id' => $id,
                    'rt_number' => return_number_btb(),
                    'returned_by' => Auth::id(),
                    'returned_at' => date('Y-m-d')
                ]);

                foreach ($data['product_ingredients'] as $value) {
                    $cek = PoSupplierDetail::where('barcode', $value['barcode'])->where('po_supplier_id', $id)->first();
                    if ($cek) {
                        $cek->update([
                            'qty_received' => (int)$cek->qty_received - (int)$value['qty_received'],
                            'qty_returned' => (int)$cek->qty_returned + (int)$value['qty_received'],
                            'qty_bonus' => (int)$cek->qty_bonus - (int)$value['qty_bonus'],
                        ]);

                        $this->ingredientStockService->createFromPoSupplier(
                            $cek->product_ingredient_id,
                            config('inventory.branch_kantor_pusat_id'),
                            $cek->product_recipe_unit_id,
                            ($value['qty_received']) * (-1),
                            $cek->id
                        );
                        $ingredient = ProductIngredient::where('id', $cek->product_ingredient_id)->first();
                        $return_po_details = new ReturnPoSupplierDetail();
                        $return_po_details->create([
                            'return_id' => $return_data->id,
                            'product_ingredient_id' => $cek->product_ingredient_id,
                            'product_recipe_unit_id' => $cek->product_recipe_unit_id,
                            'brand_id' => $cek->brand_id,
                            'qty' => $value['qty_received'],
                            'bonus' => $value['qty_bonus'],
                            'barcode' => $cek->barcode,
                            'real_price' => $ingredient['real_price'],
                            'price' => $ingredient['price'],
                            'discount' => $ingredient['discount']
                        ]);
                    }
                }

                return true;
            });
        }

        $model = $this->model->select(['id', 'po_number', 'date', 'purchasing_supplier_id', 'status', 'status_delivery', 'receipt_number', 'received_at'])->with(['purchasingSupplier:id,name,address,discount', 'poSupplierDetails:*,qty_received as old_received', 'poSupplierDetails.productRecipeUnit:id,name', 'poSupplierDetails.brand:id,name', 'poSupplierDetails.productIngredient:id,name,hpp,discount,price,real_price', 'receivePoSuppliers', 'receivePoSuppliers.receivePoSupplierDetails.productRecipeUnit:id,name', 'receivePoSuppliers.receivePoSupplierDetails.brand:id,name', 'receivePoSuppliers.receivePoSupplierDetails.productIngredient:id,name', 'returnPoSuppliers', 'returnPoSuppliers.returnPoSupplierDetails.productRecipeUnit:id,name', 'returnPoSuppliers.returnPoSupplierDetails.brand:id,name', 'returnPoSuppliers.returnPoSupplierDetails.productIngredient:id,name'])->findOrFail($id);
        return $this->response($model);
        // return $this->response(true);
    }
}
