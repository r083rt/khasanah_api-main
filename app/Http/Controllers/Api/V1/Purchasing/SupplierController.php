<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchasing\PurchasingSupplier;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
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
    public function __construct(PurchasingSupplier $model)
    {
        $this->middleware('permission:purchasing-supplier.lihat|purchasing-supplier.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:purchasing-supplier.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:purchasing-supplier.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:purchasing-supplier.hapus', [
            'only' => ['destroy']
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
        $data = $this->model->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display all of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll(Request $request)
    {
        $data = $this->model->get();

        $purchasing_supplier_ids = DB::table('product_ingredient_suppliers')->distinct('purchasing_supplier_id')->pluck('purchasing_supplier_id');
        $datas = PurchasingSupplier::select('id', 'name')->with(['productIngredients','productIngredients.unit', 'productIngredients.unitDelivery'])->whereIn('id', $purchasing_supplier_ids)->orderBy('name')->get();

        return $this->response($datas);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'nullable|string',
            'payment' => 'required|in:cash,tempo',
            'address' => 'nullable|string',
            'discount' => 'nullable|numeric',
            'day' => 'required_if:payment,tempo|numeric|min:1|max:31'
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->create($data);
        });

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
        $model = $this->model->with(['productIngredients:id,name'])->findOrFail($id);
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
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'payment' => 'required|in:cash,tempo',
            'discount' => 'nullable|numeric',
            'address' => 'nullable|string',
            'day' => 'required_if:payment,tempo|numeric|min:1|max:31'
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            return $model->update($data);
        });

        return $this->response($model);
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
            'id.*' => 'required'
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
