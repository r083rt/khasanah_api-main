<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductReturn;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReturnController extends Controller
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
    public function __construct(ProductReturn $model, StockService $stockService)
    {
        $this->middleware('permission:retur-barang.lihat|retur-barang.show|donasi-barang.lihat|donasi-barang.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:retur-barang.tambah|donasi-barang.tambah', [
            'only' => ['store', 'listProduct']
        ]);
        $this->middleware('permission:retur-barang.ubah|donasi-barang.ubah', [
            'only' => ['update', 'listProduct']
        ]);
        $this->middleware('permission:retur-barang.hapus|donasi-barang.hapus', [
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
        $data = $this->model->with(['product:id,name,product_category_id', 'product.category:id,name', 'branch:id,name', 'created_by:id,name'])
            ->search($request)
            ->sort($request)
            ->whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date);

        if ($request->type) {
            $data = $data->where('type', $request->type);
        }

        $data = $data->branch();

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
        $data = Product::select('id', 'code', 'name', 'price')->with(['stocks:id,stock,product_id'])->available(false)->search($request)->orderBy('name')->get();
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
            'qty' => 'required|integer|min:1',
            'type' => 'required|in:return,donation',
            'note' => 'nullable',
        ]);

        $cek = $this->model->where([
            'product_id' => $data['product_id'],
            'qty' => $data['qty'],
            'type' => $data['type'],
            'note' => $data['note'],
            'created_at' => date('Y-m-d H:i:s')
        ])->count();
        if ($cek) {
            return $this->response('Request terlalu cepat. Silahkan coba lagi', 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            $auth = Auth::user();
            if ($data['type'] == 'return') {
                if ($stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $data['product_id'])->first()) {
                    $oldStock = $stock->stock;
                    $stock->update([
                        'stock' => $oldStock - $data['qty']
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'] * -1,
                        'stock_old' => $oldStock,
                        'from' => 'Return Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
                    ]);
                } else {
                    ProductStock::create([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'] * -1,
                        'stock_old' => 0,
                        'from' => 'Return Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
                    ]);
                }
            } else {
                if ($stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $data['product_id'])->first()) {
                    $oldStock = $stock->stock;
                    $stock->update([
                        'stock' => $oldStock - $data['qty']
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'] * -1,
                        'stock_old' => $oldStock,
                        'from' => 'Sumbangan Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
                    ]);
                } else {
                    ProductStock::create([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['qty']
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'] * -1,
                        'stock_old' => 0,
                        'from' => 'Sumbangan Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
                    ]);
                }
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
        $model = $this->model->with(['product:id,name,product_category_id', 'product.category:id,name', 'branch:id,name', 'created_by:id,name'])->findOrFail($id);
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
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'type' => 'required|in:return,donation',
            'note' => 'nullable',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $oldQty = $model->qty;
            $model = $this->model->create($data);
            $auth = Auth::user();
            if ($data['type'] == 'return') {
                if ($stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $data['product_id'])->first()) {
                    $oldStock = $stock->stock;
                    $stock->update([
                        'stock' => ($oldStock + $oldQty) - $data['qty']
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'],
                        'stock_old' => $oldStock,
                        'from' => 'Return Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
                    ]);
                }
            } else {
                if ($stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $data['product_id'])->first()) {
                    $oldStock = $stock->stock;
                    $stock->update([
                        'stock' => ($oldStock - $oldQty) + $data['qty']
                    ]);

                    $this->stockService->createStockLog([
                        'branch_id' => $auth->branch_id,
                        'product_id' => $data['product_id'],
                        'stock' => $data['qty'],
                        'stock_old' => $oldStock,
                        'from' => 'Sumbangan Produk',
                        'table_reference' => 'product_returns',
                        'table_id' => $model->id
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
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
