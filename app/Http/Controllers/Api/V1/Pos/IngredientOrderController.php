<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Auth;

class IngredientOrderController extends Controller
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
    public function __construct(Order $model)
    {
        $this->middleware('permission:bahan-pesanan.lihat', [
            'only' => ['index', 'listProductCategory']
        ]);
        $this->middleware('permission:bahan-pesanan.download', [
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
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $model = $this->model
            ->select('id')
            ->search($request)
            ->order()
            ->with([
                'products:id,order_id,product_id,qty',
            ])
            ->whereDate('created_at', '>=', $data['start_date'])
            ->whereDate('created_at', '<=', $data['end_date']);

        if (Auth::user()->branch_id == 1) {
            if (isset($data['branch_id']) && $data['branch_id']) {
                $model = $model->where('branch_id', $data['branch_id']);
            }
        } else {
            $model = $model->where('branch_id', Auth::user()->branch_id);
        }

        $model = $model->get();
        $model = $model->pluck('products');

        $modelProducts = [];
        foreach ($model as $key => $value) {
            foreach ($value as $row) {
                $modelProducts[] = [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'qty' => $row->qty,
                ];
            }
        }

        $products = [];
        foreach ($modelProducts as $key => $value) {
            $key = $value['product_id'];
            if (!array_key_exists($key, $products)) {
                $products[$key] = [
                    'id' => $value['id'],
                    'product_id' => $key,
                    'qty' => $value['qty'],
                    'product_total' => 1
                ];
            } else {
                $products[$key]['qty'] = $products[$key]['qty'] + $value['qty'];
                $products[$key]['product_total'] = $products[$key]['product_total'] + 1;
            }
        }

        $products = collect($products);
        $productIds = collect($products)->pluck('product_id');

        $recipes = ProductRecipe::select('id', 'product_id', 'product_ingredient_id', 'product_recipe_unit_id', 'measure')->with(['ingredient:id,name,code', 'unit:id,name', 'product:id,name'])->whereIn('product_id', $productIds)->get();
        $datas = [];
        foreach ($recipes as $key => $value) {
            $row['id'] = $value->id;
            if ($cek = $products->where('product_id', $value->product_id)->first()) {
                $row['qty'] = $cek['qty'];
                $row['estimation'] = $value->measure * $cek['product_total'] * $cek['qty'];
            } else {
                $row['qty'] = 0;
                $row['estimation'] = 0;
            }

            $row['ingredient_name'] = $value->ingredient->name;
            $row['ingredient_code'] = $value->ingredient->code;
            $row['unit_code'] = $value->unit->name;
            $row['product_name'] = $value->product->name;
            $datas[] = $row;
        }

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name', 'code')->search($request)->orderBy('name')->branch()->get();
        return $this->response($data);
    }
}
