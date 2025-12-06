<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Purchasing\PoSupplierEmail;
use App\Models\Purchasing\PoSupplier;
use App\Models\Purchasing\PoSupplierDetail;
use App\Models\Purchasing\PurchasingSupplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class PoSupplierController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(PoSupplier $model)
    {
        $this->middleware('permission:po-supplier.lihat', [
            'only' => ['index', 'show']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Log::info('Index Request:', [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'purchasing_supplier_id' => $request->purchasing_supplier_id,
            'all_params' => $request->all(), // log all request parameters
        ]);
    
        $data = $this->model->with(['purchasingSupplier:id,name', 'branch'])->search($request)->sort($request);

        if ($start_date = $request->start_date ) {
            if ($end_date = $request->end_date) {
                $data = $data->where('date', '>=', $start_date)->where('date', '<=', $end_date);
            }
        }

        if ($purchasing_supplier_id = $request->purchasing_supplier_id) {
            $data = $data->where('purchasing_supplier_id', $purchasing_supplier_id);
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
        $model = $this->model->with(['purchasingSupplier:id,name', 'poSupplierDetails', 'poSupplierDetails.productIngredient:id,name', 'poSupplierDetails.productRecipeUnit:id,name', 'poSupplierDetails.brand:id,name'])->findOrFail($id);
        return $this->response($model);

        // $model = $this->model->with([
        //     'purchasingSupplier:id,name',
        //     'poSupplierDetails' => function($query) {
        //         $query->whereColumn('qty_received', '!=', 'qty');
        //     },
        //     'poSupplierDetails.productIngredient:id,name',
        //     'poSupplierDetails.productRecipeUnit:id,name',
        //     'poSupplierDetails.brand:id,name'
        // ])->findOrFail($id);
    
        // return $this->response($model);
    }

    public function partial(Request $request)
    {
        $data = $this->model->with(['purchasingSupplier:id,name', 'branch'])->search($request)->sort($request);

        if ($start_date = $request->start_date ) {
            if ($end_date = $request->end_date) {
                $data = $data->where('date', '>=', $start_date)->where('date', '<=', $end_date);
            }
        }

        if ($purchasing_supplier_id = $request->purchasing_supplier_id) {
            $data = $data->where('purchasing_supplier_id', $purchasing_supplier_id);
        }

        $data = $data->where('status_delivery','partial');

        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sendEmail($id)
    {
        $model = $this->model->findOrFail($id);
        if ($model->status == 'sent') {
            return $this->response('Email sedang/sudah terkirim', 'error', 422);
        }

        $model->update([
            'status' => 'sent'
        ]);

        $file = 'app/po_supplier/' . $model->po_number . '.pdf';
        $data = [
            'file' => $file,
            'po_number' => $model->po_number,
            'month' => month_indo($model->month),
            'year' => $model->year,
            'date' => tanggal_indo($model->date, false, false),
            'supplier' => $model->purchasingSupplier->name,
            'file_path' => storage_path($file),
            'to' => $model->purchasingSupplier->email,
            'id' => $id,
        ];

        $ingredients = [];
        foreach ($model->poSupplierDetails as $value) {
            $datas = [
                'ingredient' => $value->productIngredient->name,
                'brand' => $value->brand->name,
                'barcode' => $value->barcode,
                'qty' => $value->qty,
                'unit' => $value->productRecipeUnit->name,
            ];

            $ingredients[] = $datas;
        }

        $data['ingredients'] = $ingredients;

        if (env('SEND_MAIL')) {
            dispatch(new PoSupplierEmail($data));
        } else {
            $model->update([
                'status' => 'success'
            ]);
        }

        return $this->response(true);
    }

    public function store(Request $request){
        $data = $this->validate($request, [
            'supplierId' => 'required|integer',
            'po_number' => 'required|integer',
            'date' => 'required|date',
            'productIngredients.*' => 'required|array',
            'productIngredients.*.productId' => 'required',
            'productIngredients.*.qty' => 'required|integer',
            'note' => 'nullable'
        ]);
        
        $data = DB::connection('mysql')->transaction(function () use ($data) {

            $poSupplier = PoSupplier::create([
                'po_number' => $data['po_number'],
                'day' => date('d', strtotime($data['date'])),
                'month' => date('m', strtotime($data['date'])),
                'year' => date('Y', strtotime($data['date'])),
                'date' => $data['date'],
                'purchasing_supplier_id' => $data['supplierId'],
                'status' => 'new'
            ]);

            foreach ($data['productIngredients'] as $row) {
                $poSupplier->poSupplierDetails()->create([
                    'po_supplier_id' => $poSupplier->id,
                    'product_ingredient_id' => $row['productId'],
                    'product_recipe_unit_id' => $row['product_recipe_unit_id'],
                    'brand_id' => $row['brand_id'],
                    'qty' => $row['qty'],
                    'barcode' => $row['barcode'],
                ]);
            }

            return true;
        });

        return $this->response(true);
    }
}
