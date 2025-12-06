<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStockAdjustment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductStockAdjustmentController extends Controller
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
    public function __construct(ProductStockAdjustment $model, StockService $stockService)
    {
        $this->middleware('permission:penyesuaian-stok.lihat|penyesuaian-stok.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:penyesuaian-stok.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:penyesuaian-stok.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:penyesuaian-stok.hapus', [
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
        $created_at = $request->created_at;
        $data = $this->model->search($request)->sort($request)->with(['created_by:id,name', 'branch:id,name', 'product:id,name'])->branch()->whereDate('created_at', $created_at);
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
        $data = Product::select('id', 'code', 'name', 'price')->with(['stocks:id,stock,product_id'])->available(false)->search($request)->orderBy('code')->get();
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
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required',
            'products.*.note' => 'nullable|string',
            'products.*.category' => 'required|in:incoming-product,adjustment',
        ]);

        $auth = Auth::user();
        $products = [];
        foreach ($data['products'] as $key => $value) {
            $stock = ProductStock::where('product_id', $value['product_id'])->where('branch_id', $auth->branch_id)->first();
            if ($stock) {
                $old_stock = $stock->stock;
            } else {
                $old_stock = 0;
            }

            $products[] = [
                'product_id' => $value['product_id'],
                'qty' => $value['qty'],
                'category' => $value['category'],
                'note' => isset($value['note']) ? $value['note'] : null,
                'old_stock' => $old_stock
            ];
        }

        $data = DB::connection('mysql')->transaction(function () use ($products, $auth) {
            foreach ($products as $value) {
                $model = $this->model->create($value);

                $this->stockService->create(
                    $value['product_id'],
                    $auth->branch_id,
                    $value['qty'],
                    'Penyesuain Stok',
                    'product_stock_adjustments',
                    $model->id
                );
            }

            /**
             * Clear cache
             */
            Cache::flush();

            return true;
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
        $model = $this->model->with(['created_by:id,name', 'branch:id,name',  'product:id,name'])->branch()->findOrFail($id);
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
        //
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

        /**
         * Clear cache
         */
        Cache::flush();

        return $this->response($data ? true : false);
    }
}
