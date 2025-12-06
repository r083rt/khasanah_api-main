<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAvailable;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
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
    public function __construct(Product $model)
    {
        $this->middleware('permission:barang-stok.lihat', [
            'only' => ['index']
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
        $branchId = Auth::user()->branch_id;
        $productAvailableId = ProductAvailable::select('product_id')->branch()->pluck('product_id');
        $data = $this->model;

        if ($branchId != 1) {
            $data =  $data->select('id', 'code', 'name', DB::raw('(SELECT SUM(stock) FROM product_stocks WHERE product_stocks.product_id = products.id AND product_stocks.branch_id = ' . $branchId . ') as total_stocks'));
        } else {
            $data =  $data->select('id', 'code', 'name', DB::raw('(SELECT SUM(stock) FROM product_stocks WHERE product_stocks.product_id = products.id) as total_stocks'));
        }
        $data = $data->whereIn('id', $productAvailableId)->search($request);

        if ($request->sort == 'stock') {
            $data = $data->orderBy('total_stocks', $request->sort_type ?? 'asc');
        } else {
            $data = $data->sort($request);
        }

        $data = $data->paginate($this->perPage($data));

        foreach ($data->items() as $value) {
            $stocks = ProductStock::with(['branch:id,name'])->where('product_id', $value->id);
            if ($branchId != 1) {
                $stocks =  $stocks->where('branch_id', $branchId);
            }

            $value->stocks = $stocks->get();
        }

        return $this->response($data);
    }
}
