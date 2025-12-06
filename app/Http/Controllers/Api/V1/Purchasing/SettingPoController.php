<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Purchasing\PoSupplier;
use App\Models\Inventory\Brand;
use App\Models\Branch;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\ForecastConversionSettingPo;
use App\Models\Purchasing\ForecastConversionSettingPoDelivery;
use App\Models\Purchasing\ForecastConversionSettingPoSupplier;
use App\Models\Purchasing\ForecastConversionSettingPoSupplierDelivery;
use App\Models\Purchasing\ForecastConversionSettingPoQtySupplier;
use App\Models\Purchasing\ForecastConversionSettingPoSupplierQtyDelivery;
use App\Models\Purchasing\PoSupplier as PurchasingPoSupplier;
use App\Models\Purchasing\PurchasingSupplier;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\Reporting\SettingPoExport;

use App\Exports\Reporting\SettingPoQtyExport;

class SettingPoController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ForecastConversionApproval $model, BranchService $branchService)
    {
        $this->middleware('permission:setting-po.lihat|setting-po.ubah', [
            'only' => ['index', 'showIngredient', 'showSupplier', 'listBranch']
        ]);
        $this->middleware('permission:setting-po.ubah', [
            'only' => ['store']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model->with(['branch:id,name'])->search($request)->whereIn('status', ['approved', 'setting-po', 'generating', 'submitted'])->sort($request);

        if ($status = $request->status) {
            $data = $data->where('status', $status);
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
        $productIngredientId = $request->product_ingredient_id;
        $supplierIds = DB::table('product_ingredient_suppliers')->where('product_ingredient_id',  $productIngredientId)->pluck('purchasing_supplier_id');
        $datas = PurchasingSupplier::select('id', 'name')->whereIn('id', $supplierIds)->get();

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $datas = $this->branchService->getAll();

        return $this->response($datas);
    }

    public function brandProduct(Request $request, $id)
    {
        
        $datas = ProductIngredient::where('brand_id', $id)->get();

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
        $model = $this->model->with([
            'branch:id,name',
            'forecastConversionSettingPo',
            'forecastConversionSettingPo.productRecipeUnit:id,name',
            'forecastConversionSettingPo.productIngredient:id,name,brand_id',
            'forecastConversionSettingPo.brand:id,name',
            'forecastConversionSettingPo.forecastConversionSettingPoSuppliers',
            'forecastConversionSettingPo.forecastConversionSettingPoSuppliers.forecastConversionSettingPoSupplierDeliveries',
            'forecastConversionSettingPo.forecastConversionSettingPoSuppliers.purchasingSupplier:id,name',
        ])->findOrFail($id);

        $po1_model = [];
        if($model->type == "so") {
            $po1_model = $this->model->with([
                'branch:id,name',
                'forecastConversionSettingPo',
                'forecastConversionSettingPo.productRecipeUnit:id,name',
                'forecastConversionSettingPo.productIngredient:id,name,brand_id',
                'forecastConversionSettingPo.brand:id,name',
                'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers',
                'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers.forecastConversionSettingPoSupplierQtyDeliveries',
                'forecastConversionSettingPo.forecastConversionSettingPoQtySuppliers.purchasingSupplier:id,name',
            ])->findOrFail($model->parent_id);
        }

        return $this->response([
            'is_submitted' => $model->status == "setting-po" ? true : false,
            'data' => $model,
            'po_1' => $po1_model
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $data = $this->validate($request, [
            'datas.*' => 'required|array',
            'datas.*.id' => 'required|exists:forecast_conversion_setting_po,id',
            'datas.*.suppliers.*.purchasing_supplier_id' => 'required|exists:purchasing_suppliers,id',
            'datas.*.suppliers.*.period_1.*.date' => 'nullable|numeric',
            'datas.*.suppliers.*.period_1.*.qty' => 'nullable|numeric',
            'datas.*.suppliers.*.period_1.*.branch' => 'nullable',
            // 'datas.*.suppliers.*.period_2.*.date' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_2.*.qty' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_3.*.date' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_3.*.qty' => 'nullable|numeric',
        ], [], [
            'datas.*.suppliers.*.purchasing_supplier_id' => 'Supplier',
            'datas.*.suppliers.*.period_1.*.date' => 'Tanggal',
            'datas.*.suppliers.*.period_1.*.qty' => 'Jumlah',
        ]);
        // dd($data);

        $model = ForecastConversionApproval::findOrFail($id);
        if ($model->status == 'setting-po') {
            return $this->response('Anda sudah melakukan submit.', 'error', 422);
        }

        // //Update validasti qty
        // foreach ($data['datas'] as $value) {
        //     $cek = ForecastConversionSettingPo::find($value['id']);
        //     if (((int) $value['qty_1'] + (int) $value['qty_2'] + (int) $value['qty_3']) != (int) $cek->qty_total) {
        //         return $this->response('Ada jumlah merk bahan tidak sesuai dengan jumlah total', 'error', 422);
        //     }
        // }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            foreach ($data['datas'] as $value) {
                $settingPo = ForecastConversionSettingPo::where('id', $value['id'])->first();
                if ($settingPo) {
                    ForecastConversionSettingPoSupplier::where('forecast_conversion_setting_po_id', $settingPo->id)->delete();
                    $totalQty = 0;
                    foreach ($value['suppliers'] as $row) {
                        $supplier = ForecastConversionSettingPoSupplier::create([
                            'forecast_conversion_setting_po_id' => $settingPo->id,
                            'purchasing_supplier_id' => $row['purchasing_supplier_id'],
                        ]);

                        foreach($row['period_1'] as $periode_1){
                            if(is_array($periode_1['branch'])){
                                foreach($periode_1['branch'] as $branch){
                                    ForecastConversionSettingPoSupplierDelivery::create([
                                        'forecast_conversion_setting_po_supplier_id' => $supplier->id,
                                        'period' => 'period_1',
                                        'date' => $periode_1['date'],
                                        'qty' => $branch['qty'],
                                        'branch' => $branch['branch_id']
                                    ]);
        
                                    $totalQty = $totalQty + $branch['qty'];
                                }
                            } else {
                                ForecastConversionSettingPoSupplierDelivery::create([
                                    'forecast_conversion_setting_po_supplier_id' => $supplier->id,
                                    'period' => 'period_1',
                                    'date' => $periode_1['date'],
                                    'qty' => $periode_1['qty'],
                                    'branch' => 1
                                ]);
    
                                $totalQty = $totalQty + $periode_1['qty'];
                            }
                            
                        }
                    }

                    $settingPo->update([
                        'qty_used' => $totalQty,
                        'qty_remaining' => $settingPo->qty_real - $totalQty,
                    ]);
                }

                if(isset($value['real_price'])){
                    $ingredient = ProductIngredient::where('id', $value['product_ingredient_id'])->first();
                    $ingredient->real_price = $value['real_price'];
                    $ingredient->update();
                }
            }

            return $model->update([
                'status' => 'setting-po'
            ]);
        });

        /**
         * Generate jadi po supplier
         */
        $datas = [
            'forecast_conversion_approval_id' => $id,
        ];

        dispatch(new PoSupplier($datas));

        return $this->response(true);
    }

    public function store_qty(Request $request, $id)
    {
        $data = $this->validate($request, [
            'datas.*' => 'required|array',
            'datas.*.id' => 'required|exists:forecast_conversion_setting_po,id',
            'datas.*.product_ingredient_id' => 'nullable',
            'datas.*.qty_total' => 'nullable',
            // 'datas.*.suppliers.*.purchasing_supplier_id' => 'required|exists:purchasing_suppliers,id',
            'datas.*.suppliers.*.period_1.*.date' => 'nullable|numeric',
            'datas.*.suppliers.*.period_1.*.qty' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_2.*.date' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_2.*.qty' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_3.*.date' => 'nullable|numeric',
            // 'datas.*.suppliers.*.period_3.*.qty' => 'nullable|numeric',
        ], [], [
            // 'datas.*.suppliers.*.purchasing_supplier_id' => 'Supplier',
            'datas.*.suppliers.*.period_1.*.date' => 'Tanggal',
            'datas.*.suppliers.*.period_1.*.qty' => 'Jumlah',
        ]);
        // dd($data);

        $model = ForecastConversionApproval::findOrFail($id);
        if ($model->status == 'setting-po') {
            return $this->response('Anda sudah melakukan submit.', 'error', 422);
        }

        // //Update validasti qty
        // foreach ($data['datas'] as $value) {
        //     $cek = ForecastConversionSettingPo::find($value['id']);
        //     if (((int) $value['qty_1'] + (int) $value['qty_2'] + (int) $value['qty_3']) != (int) $cek->qty_total) {
        //         return $this->response('Ada jumlah merk bahan tidak sesuai dengan jumlah total', 'error', 422);
        //     }
        // }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            foreach ($data['datas'] as $value) {
                $settingPo = ForecastConversionSettingPo::where('id', $value['id'])->first();
                if ($settingPo) {
                    if($settingPo->product_ingredient_id != $value['product_ingredient_id']){
                        $ingredient = ProductIngredient::find($value['product_ingredient_id']);
                        $brandId = $ingredient->brand_id;

                        $brand = ProductIngredientBrand::where('product_ingredient_id', $value['product_ingredient_id'])->where('product_recipe_unit_id', $ingredient->product_ingredient_unit_delivery_id)->first();
                        $barcode = $brand->barcode;

                        $settingPo->barcode = $barcode;
                        $settingPo->product_ingredient_id = $value['product_ingredient_id'];
                        $settingPo->qty_total = $value['qty_total'];
                        $settingPo->update();
                    }
                    ForecastConversionSettingPoSupplier::where('forecast_conversion_setting_po_id', $settingPo->id)->delete();
                    ForecastConversionSettingPoQtySupplier::where('forecast_conversion_setting_po_id', $settingPo->id)->delete();
                    $totalQty = 0;
                    foreach ($value['suppliers'] as $row) {
                        $supplier = ForecastConversionSettingPoSupplier::create([
                            'forecast_conversion_setting_po_id' => $settingPo->id,
                            // 'purchasing_supplier_id' => $row['purchasing_supplier_id'], 
                        ]);

                        foreach ($row['period_1'] as $periode_1) {
                            ForecastConversionSettingPoSupplierDelivery::create([
                                'forecast_conversion_setting_po_supplier_id' => $supplier->id,
                                'period' => 'period_1',
                                'date' => $periode_1['date'],
                                'qty' => $periode_1['qty'],
                            ]);

                            $totalQty = $totalQty + $periode_1['qty'];
                        }

                        $qty_supplier = ForecastConversionSettingPoQtySupplier::create([
                            'forecast_conversion_setting_po_id' => $settingPo->id,
                            // 'purchasing_supplier_id' => $row['purchasing_supplier_id'], 
                        ]);

                        foreach ($row['period_1'] as $periode_1) {
                            ForecastConversionSettingPoSupplierQtyDelivery::create([
                                'forecast_conversion_setting_po_supplier_id' => $qty_supplier->id,
                                'period' => 'period_1',
                                'date' => $periode_1['date'],
                                'qty' => $periode_1['qty'],
                            ]);
                        }

                    }

                    $settingPo->update([
                        'qty_used' => $totalQty,
                        'qty_remaining' => $settingPo->qty_real - $totalQty,
                    ]);
                }
            }

        });

        /**
         * Generate jadi po supplier
         */
        $datas = [
            'forecast_conversion_approval_id' => $id,
        ];

        // dispatch(new PoSupplier($datas));

        return $this->response(true);
    }

    public function generateNumber(Request $request){
        $number = date('YmdHis') . rand(1000,9999);

        return $this->response($number);
    }

    // excel export

    public function export(Request $request, $id)
    {
        $model = ForecastConversionApproval::findOrFail($id);

        $fileName = 'setting-po-' . $model->pr_id . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new SettingPoExport($id), $fileName);
    }

    public function qtyexport(Request $request, $id)
    {
        $model = ForecastConversionApproval::findOrFail($id);

        $fileName = 'setting-po-qty-' . $model->pr_id . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new SettingPoQtyExport($id), $fileName);
    }
}
