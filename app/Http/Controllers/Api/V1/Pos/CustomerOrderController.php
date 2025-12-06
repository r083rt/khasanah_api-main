<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomerOrderController extends Controller
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
        $this->middleware('permission:pelanggan-pesanan.lihat', [
            'only' => ['index', 'listProductCategory', 'show']
        ]);
        $this->middleware('permission:pelanggan-pesanan.download', [
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
            'customer_id' => 'required|integer|exists:customers,id',
            'status' => 'required|in:month,not-paid'
        ]);

        $model = $this->model->search($request)->branch()->order()->with([
            'createdBy:id,name',
            'branch:id,name',
            'category:id,name',
            'products:id,product_name,qty,order_id',
            'customer:id,name',
            'receivedBy:id,name',
            'payments.createdBy:id,name'
        ])
        ->where('customer_id', $data['customer_id']);

        if (in_array($data['status'], ['not-paid'])) {
            $model = $model->where('status_payment', $data['status']);
        }

        if (in_array($data['status'], ['month', 'pickup'])) {
            $model = $model->whereDate('created_at', '>', Carbon::now()->subDays(30));
        }

        $model = $model->whereDate('created_at', '>=', $data['start_date'])->whereDate('created_at', '<=', $data['end_date'])->get();
        return $this->response($model);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listCustomer(Request $request)
    {
        $data = Customer::select('id', 'name', 'phone')->search($request)->orderBy('name')->get();
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
            'branch:id,name',
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
