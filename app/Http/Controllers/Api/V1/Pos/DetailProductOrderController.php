<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OrderProduct;
use App\Models\ProductCategory;
use App\Models\ProductStock;

class DetailProductOrderController extends Controller
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
    public function __construct(OrderProduct $model)
    {
        $this->middleware('permission:barang-pesanan.lihat', [
            'only' => ['index', 'listProductCategory']
        ]);
        $this->middleware('permission:barang-pesanan.download', [
            'only' => ['download']
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
        $data = $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'product_category_id' => 'nullable|exists:product_categories,id',
        ]);

        $model = $this->model->select('id', 'order_id', 'product_name', 'discount', 'qty', 'total_price', 'product_id')->search($request)
            ->with([
                'orders:id,branch_id,product_category_id',
                'orders.branch:id,name',
                'orders.category:id,name',
            ])
            ->whereHas('orders', function ($query) use ($data) {
                $query->branch()->order();

                if (isset($data['product_category_id']) && $data['product_category_id'] != '') {
                    $query->where('product_category_id', $data['product_category_id']);
                }

                $query->whereDate('created_at', '>=', $data['start_date'])
                    ->whereDate('created_at', '<=', $data['end_date']);
            })
            ->get();

        foreach ($model as $value) {
            $currentStock = ProductStock::where('product_id', $value->product_id)->branch()->sum('stock');
            $value['current_stock'] = (int)$currentStock;
        }

        return $this->response($model);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProductCategory(Request $request)
    {
        $data = ProductCategory::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }
}
