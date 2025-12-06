<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;

class HistoryOrderController extends Controller
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
        $this->middleware('permission:history-pesanan.lihat', [
            'only' => ['index', 'listProductCategory']
        ]);$this->middleware('permission:history-pesanan.download', [
            'only' => ['download']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'branch_id' => 'nullable|integer',
        ]);

        $model = $this->model->search($request)->order()->with([
            'createdBy:id,name',
            'branch:id,name,address',
            'category:id,name',
            'products:id,product_name,qty,order_id',
            'customer:id,name',
            'receivedBy:id,name',
            'payments.createdBy:id,name',
            'refundBy:id,name'
        ]);

        if (isset($data['product_category_id']) && $data['product_category_id'] != '') {
            $model = $model->where('product_category_id', $data['product_category_id']);
        }

        if (Auth::user()->branch_id == 1) {
            if (isset($data['branch_id']) && $data['branch_id']) {
                $model = $model->where('branch_id', $data['branch_id']);
            }
        } else {
            $model = $model->where('branch_id', Auth::user()->branch_id);
        }
        // $model = $model->where('branch_id', Auth::user()->branch_id);


        $model = $model->where('status', 'completed')
            // ->where('status_payment', 'paid')
            // ->where('status_pickup', 'done')
            ->whereDate('created_at', '>=', $data['start_date'])
            ->whereDate('created_at', '<=', $data['end_date'])
            ->sort($request)
            ->get();
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

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name', 'code')->branch()->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->with([
            'createdBy:id,name',
            'branch:id,name,address',
            'category:id,name',
            'products:id,product_name,qty,order_id,product_price as price,total_price,discount',
            'customer:id,name',
            'receivedBy:id,name',
            'payments.createdBy:id,name',
            'refundBy:id,name'
        ])
        ->branch()->order()->findOrFail($id);

        return $this->response($model);
    }
}
