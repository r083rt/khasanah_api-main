<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoOrderProductStatusLog;
use App\Models\Management\Shipping;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
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
    public function __construct(PoOrderProduct $model)
    {
        $this->middleware('permission:po-pesanan-produk.lihat|po-pesanan-ingredient.lihat', [
            'only' => ['index']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function poOrderBadge()
    {
        $data['product'] = $this->model->where('status', 'new')->available()->branch()->count();
        $data['ingredient']  = PoOrderIngredient::where('status', 'new')->available()->branch()->count();
        $data['total']  = $data['product'] + $data['ingredient'];
        return $this->response($data);
    }
}
