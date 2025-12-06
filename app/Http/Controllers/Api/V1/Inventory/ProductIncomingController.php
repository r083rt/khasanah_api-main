<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductIncoming;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use DB;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;

class ProductIncomingController extends Controller
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
    public function __construct(ProductIncoming $model, StockService $stockService)
    {
        $this->middleware('permission:barang-masuk.lihat|barang-masuk.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:barang-masuk.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:barang-masuk.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:barang-masuk.hapus', [
            'only' => ['destroy']
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
        $data = $this->model->select('id', 'created_by', 'product_id', 'total', 'price', 'total_price')->with(['product:id,name,code', 'user:id,name'])->branch()->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name', 'code', 'price')->search($request)->orderBy('name')->get();
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
            'product_id' => 'required|exists:products,id',
            'total' => 'required|integer|min:1',
        ]);

        $product = Product::find($data['product_id'])->first();
        $data['price'] = $product->price;
        $data['total_price'] = $product->price * $data['total'];
        $data['created_by'] = Auth::user()->id;
        $data['branch_id'] = Auth::user()->branch_id;

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);

            $stock = ProductStock::where('product_id', $data['product_id'])->where('branch_id', Auth::user()->branch_id)->first();
            if ($stock) {
                $totalStock = $stock->stock + $data['total'];
                $stock->update([
                    'stock' => $totalStock
                ]);
            } else {
                ProductStock::create([
                    'product_id' => $data['product_id'],
                    'branch_id' => Auth::user()->branch_id,
                    'stock' => $data['total']
                ]);
            }

            return $model;
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
        $data = $this->model->select('id', 'created_by', 'product_id', 'total', 'price', 'total_price')->with(['product:id,name,code', 'user:id,name'])->branch()->findOrFail($id);
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
            'product_id' => 'required|exists:products,id',
            'total' => 'required|integer',
        ]);
        $product = Product::find($data['product_id'])->first();
        $data['price'] = $product->price;
        $data['total_price'] = $product->price * $data['total'];

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $firstStock = $model->total;
            $model->update($data);

            $stock = ProductStock::where('product_id', $data['product_id'])->where('branch_id', Auth::user()->branch_id)->first();
            if ($stock) {
                $totalStock = ($stock->stock - $firstStock) + $data['total'];
                $stock->update([
                    'stock' => $totalStock
                ]);
            } else {
                ProductStock::create([
                    'product_id' => $data['product_id'],
                    'branch_id' => Auth::user()->branch_id,
                    'stock' => $data['total']
                ]);
            }

            return $model;
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
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
