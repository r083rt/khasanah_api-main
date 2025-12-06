<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Supplier\SupplierResource;
use App\Models\Branch;
use App\Models\Management\BranchSupplier;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
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
    public function __construct(Branch $model)
    {
        $this->middleware('permission:supplier.lihat|supplier.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:supplier.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:supplier.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:supplier.hapus', [
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
        $data = $this->model->has('suppliers')->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return SupplierResource::collection($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $supplier = $request->supplier;
        if ($supplier) {
            $data = Branch::select('id', 'name')->search($request)->orderBy('name')->get();
        } else {
            $branchIds = BranchSupplier::select('branch_id')->pluck('branch_id')->unique()->values();
            $data = Branch::select('id', 'name')->whereNotIn('id', $branchIds)->search($request)->orderBy('name')->get();
        }

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
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
            'branch_id' => 'required|exists:branches,id',
            'suppliers' => 'required|array',
            'suppliers.*.branch_id' => 'required|exists:branches,id',
            'suppliers.*.products' => 'required|array',
            'suppliers.*.products.*.product_id' => 'required|exists:products,id',
        ], [
            'branch_id.exists' => 'Cabang yang dipilih tidak valid',
            'suppliers.*.branch_id.exists' => 'Cabang yang dipilih tidak valid',
            'suppliers.*.products.*.product_id.exists' => 'Produk yang dipilih tidak valid',
        ]);

        $model = $this->model->findOrFail($data['branch_id']);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            foreach ($data['suppliers'] as $value) {
                $supplier = $model->suppliers()->create([
                    'supplier_id' => $value['branch_id']
                ]);
                $supplier->products()->attach($value['products']);
            }

            return $model;
        });

        return $this->response($data);
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
            'branch_id' => 'required|exists:branches,id',
            'suppliers' => 'required|array',
            'suppliers.*.branch_id' => 'required|exists:branches,id',
            'suppliers.*.products' => 'required|array',
            'suppliers.*.products.*.product_id' => 'required|exists:products,id',
        ], [
            'branch_id.exists' => 'Cabang yang dipilih tidak valid',
            'suppliers.*.branch_id.exists' => 'Cabang yang dipilih tidak valid',
            'suppliers.*.products.*.product_id.exists' => 'Produk yang dipilih tidak valid',
        ]);

        $model = $this->model->findOrFail($data['branch_id']);

        $data = DB::connection('mysql')->transaction(function () use ($data, $id, $model) {
            $model->suppliers()->delete();

            foreach ($data['suppliers'] as $value) {
                $supplier = $model->suppliers()->create([
                    'supplier_id' => $value['branch_id']
                ]);
                $supplier->products()->attach($value['products']);
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
            'id.*' => 'integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return BranchSupplier::whereIn('branch_id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
