<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ClosingJob;
use App\Jobs\MonitoringClosingSummary\FirstStock;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Pos\Closing;
use App\Models\Pos\Expense;
use App\Models\Pos\OrderPayment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClosingController extends Controller
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
    public function __construct(Closing $model, StockService $stockService)
    {
        $this->middleware('permission:closing.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:closing.tambah', [
            'only' => ['store']
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
        $now = date('Y-m-d');
        $auth = Auth::user();

        $cek = Closing::with(['branch:id,name'])
            ->whereDate('created_at', $now)
            ->where('created_by', $auth->id)
            ->with('products')
            ->branch()
            ->first();

        if (!is_null($cek)) {
            if (is_null($cek->total_income) && is_null($cek->past_income) && is_null($cek->initial_capital)) {
                $cek->is_editable_money = true;
            } else {
                $cek->is_editable_money = false;
            }

            $cek->is_editable = false;

            $branch_id = $auth->branch_id;
            $branch = Branch::where('id', $branch_id)->first();
            $cek->initial_capital = $branch ? $branch->initial_capital : 0;

            if (is_null($cek->past_income)) {
                $cek->past_income = $this->getPastIncome();
            }

            $datas = $cek;
        } else {
            $products = Product::select('id', 'code as product_code', 'name as product_name', 'product_category_id')
                ->with('stocks:id,stock,product_id')
                ->whereNotIn('product_category_id', config('pos.closing.except_product_category_id'))
                ->available()
                ->orderByRaw("FIELD(product_category_id, 1, 14) DESC")
                ->orderBy('code')
                ->get();

            foreach ($products as $key => $value) {
                $value->stock_system = $value->stocks ? $value->stocks->first() ? $value->stocks->first()->stock : null : null;
                $value->stock_real = null;
                $value->difference = $value->stock_system;
                $value->note = null;
            }
            $branch_id = $auth->branch_id;
            $branch = Branch::where('id', $branch_id)->first();
            $datas = [
                'branch_id' => $branch_id,
                'branch' => [
                    'id' => $branch_id,
                    'name' => Branch::select('name')->find($branch_id)->name,
                ],
                'total_income' => 0,
                'past_income' => $this->getPastIncome(),
                'initial_capital' => $branch ? $branch->initial_capital : 0,
                'products' => $products,
                'is_editable' => true,
                'is_editable_money' => true
            ];
        }

        return $this->response($datas);
    }

   /**
     * Get pas income
     *
     * @return integer
     */
    private function getPastIncome()
    {
        $now = date('Y-m-d');
        $auth = Auth::user();

        $data = Closing::select('cashier_income')->whereDate('created_at', $now)->where('created_by', '!=', $auth->id)->branch()->orderBy('id', 'desc')->first();
        return $data ? $data->cashier_income : null;
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
            'products.*.id' => 'required|exists:products,id',
            'products.*.stock_system' => 'required|integer',
            'products.*.stock_real' => 'required|integer',
            'products.*.note' => 'nullable',
        ]);

        $now = date('Y-m-d');
        $auth = Auth::id();
        $closing = Closing::select('id')->whereDate('created_at', $now)->where('created_by', $auth)->count();
        if ($closing > 0) {
            return $this->response('Anda hanya bisa melakukan Closing sebanyak satu kali dalam sehari.', 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            $allData = [
                'products' => $data['products'],
                'closing_id' => $model->id,
                'branch_id' => Auth::user()->branch_id,
                'user_id' => Auth::id()
            ];
            // dispatch(new ClosingJob($allData));
            dispatch(new FirstStock($allData));

            $product = Product::select('id', 'name', 'code')->get();
            foreach ($data['products'] as $value) {
                $product = $product->where('id', $value['id'])->first();
                $difference = ($value['stock_system'] - $value['stock_real']) * -1;
                $model->products()->create([
                    'product_id' => $value['id'],
                    'product_name' => $product ? $product->name : null,
                    'product_code' => $product ? $product->code : null,
                    'stock_system' => $value['stock_system'],
                    'stock_real' => $value['stock_real'],
                    'difference' => $difference,
                    'note' => $value['note'],
                ]);

                $this->updateStock($value['id'], $value['stock_real'], $model->id, $difference, $value['stock_system']);
            }

            return $model;
        });


        return $this->response($data ? true : false);
    }

    /**
     * Update Stock
     *
     * @param array $products
     */
    public function updateStock($productID, $stock, $closingId, $difference, $stock_system)
    {
        $auth = Auth::user();
        ProductStock::where('product_id', $productID)->where('branch_id', $auth->branch_id)->update([
            'stock' => $stock
        ]);

        $this->stockService->createStockLog([
            'branch_id' => $auth->branch_id,
            'product_id' => $productID,
            'stock' => $difference,
            'stock_old' => $stock_system,
            'from' => 'Closing',
            'table_reference' => 'closings',
            'table_id' => $closingId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMoney(Request $request)
    {
        $data = $this->validate($request, [
            'total_income' => 'required|integer|min:1',
            'past_income' => 'required|integer',
            'initial_capital' => 'required|integer',
        ]);

        $now = date('Y-m-d');
        $auth = Auth::id();
        $model = Closing::whereDate('created_at', $now)->where('created_by', $auth)->first();
        if ($model) {
            if (!is_null($model->total_income) && !is_null($model->past_income) && !is_null($model->initial_capital)) {
                return $this->response('Anda hanya bisa melakukan Closing Uang sebanyak satu kali dalam sehari.', 'error', 422);
            }
        } else {
            return $this->response('Anda belum melakukan Closing produk.', 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $now) {
            $model->update($data);

            $cost = $this->cost();
            $paymentCash = $this->paymentCash();
            $paymentNonCash = $this->paymentNonCash();
            $salesNonCash = $this->salesNonCash();
            $salesCash = $this->salesCash();
            $dpOrderCash = $this->dpOrderCash();
            $dpOrderNonCash = $this->dpOrderNonCash();
            $dpPickupCash = $this->dpPickupCash();
            $dpPickupNonCash = $this->dpPickupNonCash();
            $credit = $this->credit();
            $refund = $this->refund($now);
            $cashierDeposit = $paymentCash['value'] + $salesCash['value'] + $dpOrderCash['value'] + $dpPickupCash['value'];
            $centralSystem = $this->centralSystem()['value'];
            $depositeDifference = ($model->cashier_income + $cost['value'] + $paymentNonCash['value'] + $salesNonCash['value'] + $dpOrderNonCash['value'] + $dpPickupNonCash['value'] + $refund['value']) - $centralSystem;
            $localSystem = $centralSystem;

            $model->detail()->create([
                'local_system' => $localSystem,
                'central_system' => $centralSystem,
                'cost' => $cost['value'],
                'payment_cash' => $paymentCash['value'],
                'payment_noncash' => $paymentNonCash['value'],
                'sales_cash' => $salesCash['value'],
                'sales_noncash' => $salesNonCash['value'],
                'dp_cash_order' => $dpOrderCash['value'],
                'dp_noncash_order' => $dpOrderNonCash['value'],
                'dp_cash_withdrawal' => $dpPickupCash['value'],
                'dp_noncash_withdrawal' => $dpPickupNonCash['value'],
                'credit' => $credit['value'],
                'local_central_difference' => $localSystem - $centralSystem,
                'refund' => $refund['value'],
                'deposit_difference' => $depositeDifference,
                'cashier_deposit' => $cashierDeposit
            ]);

            $model->reference()->create([
                'central_system_reference' => $this->centralSystem()['id'],
                'cost_reference' => $cost['id'],
                'payment_cash_reference' => $paymentCash['id'],
                'payment_noncash_reference' => $paymentNonCash['id'],
                'sales_cash_reference' => $salesCash['id'],
                'sales_noncash_reference' => $salesNonCash['id'],
                'dp_cash_order_reference' => $dpOrderCash['id'],
                'dp_noncash_order_reference' => $dpOrderNonCash['id'],
                'dp_cash_withdrawal_reference' => $dpPickupCash['id'],
                'dp_noncash_withdrawal_reference' => $dpPickupNonCash['id'],
                'credit_reference' => $credit['id'],
                'refund_reference' => $refund['id'],
            ]);

            return $model;
        });

        return $this->response($data ? true : false);
    }

    /**
     * Central System
     *
     * @return array
     */
    private function centralSystem()
    {
        $cashier = Order::select('id', 'total_price')->cashier()->branch(false)->now()->byMe()->get();
        $sale = $cashier->pluck('id')->toArray();
        $order = Order::select('id', 'total_price')->whereDate('received_date', date('Y-m-d'))->order()->receivedByMe()->get();
        $orderIds = $order->pluck('id')->toArray();
        $result = array_merge($sale, $orderIds);

        return [
            'id' => [
                'orders' => $result,
            ],
            'value' => $cashier->sum('total_price') + $order->sum('total_price')
        ];
    }

    /**
     * Cost
     *
     * @return array
     */
    private function cost()
    {
        $data = Expense::select('id', 'total_cost')->branch(false)->now()->byMe()->get();
        return [
            'id' => $data->pluck('id')->toArray(),
            'value' => $data->sum('total_cost')
        ];
    }

    /**
     * Payment Cash
     *
     * @return array
     */
    private function paymentCash()
    {
        $data = OrderPayment::select('id', 'total_price', 'order_id')->cash()->whereIn('type', ['payment-before-taken', 'payment-after-taken'])->cash()
                ->whereHas('order', function ($query) {
                    $query = $query->receivedByMe()->branch(false);
                });

        $data = $data->now()->get();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = [
                'order_id' => $row->order_id,
                'order_payment_ids' => [$row->id]
            ];
        }

        return [
            'id' => $ids,
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * Payment Non Cash
     *
     * @return array
     */
    private function paymentNonCash()
    {
        $data = OrderPayment::select('id', 'total_price', 'order_id')->whereIn('type', ['payment-before-taken', 'payment-after-taken'])->nonCash()
            ->whereHas('order', function ($query) {
                $query = $query->receivedByMe()->branch(false);
            });

        $data = $data->now()->get();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = [
                'order_id' => $row->order_id,
                'order_payment_ids' => [$row->id]
            ];
        }

        return [
            'id' => $ids,
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * Sales Cash
     *
     * @return array
     */
    private function salesCash()
    {
        $data = Order::select('id', 'total_price')->cashier()->cash()->branch(false)->whereNull('refund_dp_date')->now()->receivedByMe()->get();
        return [
            'id' => $data->pluck('id')->toArray(),
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * Sales Non Cash
     *
     * @return array
     */
    private function salesNonCash()
    {
        $data = Order::select('id', 'total_price')->cashier()->nonCash()->branch(false)->whereNull('refund_dp_date')->now()->receivedByMe()->get();
        return [
            'id' => $data->pluck('id')->toArray(),
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * DP Order Cash
     *
     * @return array
     */
    private function dpOrderCash()
    {
        $data = OrderPayment::select('id', 'total_price', 'order_id')->cash()->whereIn('type', ['dp', 'paid'])
                ->whereHas('order', function ($query) {
                    $query = $query->receivedByMe()->branch(false);
                });

        $data = $data->now()->get();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = [
                'order_id' => $row->order_id,
                'order_payment_ids' => [$row->id]
            ];
        }

        return [
            'id' => $ids,
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * DP Order Non Cash
     *
     * @return array
     */
    private function dpOrderNonCash()
    {
        $data = OrderPayment::select('id', 'total_price', 'order_id')->nonCash()->whereIn('type', ['dp', 'paid'])
                ->whereHas('order', function ($query) {
                    $query = $query->receivedByMe()->branch(false);
                });

        $data = $data->now()->get();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = [
                'order_id' => $row->order_id,
                'order_payment_ids' => [$row->id]
            ];
        }

        return [
            'id' => $ids,
            'value' => $data->sum('total_price')
        ];
    }

    /**
     * DP Pickup Cash
     *
     * @return array
     */
    private function dpPickupCash()
    {
        $data = Order::with(['payments'])->where('received_by', Auth::id())->whereDate('received_date', date('Y-m-d'))->where('status_pickup', 'done')->where('type', 'order')->branch(false)->receivedByMe()->get();

        $ids = [];
        $value = 0;
        foreach ($data as $row) {
            $cek = $row->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', 1)->first();
            if ($cek) {
                $ids[] = [
                    'order_id' => $row->id,
                    'order_payment_ids' => [$cek->id]
                ];
                $value += $cek->total_price;
            }
        }

        return [
            'id' => $ids,
            'value' => $value
        ];
    }

    /**
     * DP Pickup Non Cash
     *
     * @return array
     */
    private function dpPickupNonCash()
    {
        $data = Order::with(['payments'])->where('received_by', Auth::id())->whereDate('received_date', date('Y-m-d'))->where('status_pickup', 'done')->where('type', 'order')->branch(false)->receivedByMe()->get();

        $ids = [];
        $value = 0;
        foreach ($data as $row) {
            $cek = $row->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', '!=', 1)->first();
            if ($cek) {
                $ids[] = [
                    'order_id' => $row->id,
                    'order_payment_ids' => [$cek->id]
                ];
                $value += $cek->total_price;
            }
        }

        return [
            'id' => $ids,
            'value' => $value
        ];
    }

    /**
     * Credit
     *
     * @return array
     */
    private function credit()
    {
        $datas = Order::select('id', 'total_price', 'pay')
            ->with(['payments:id,total_price,order_id,type'])
            ->order()
            ->branch(false)
            ->where('status_payment', 'not-paid')
            ->where('status_pickup', 'done')
            ->whereNull('refund_dp_date')
            ->whereDate('received_date', date('Y-m-d'))
            ->receivedByMe()
            ->get();

        $ids = [];
        $totalValue = 0;
        foreach ($datas as $row) {
            $ids[] = $row->id;
            $pay = $row->payments->sum('total_price');
            $totalValue = $totalValue + ($row->total_price - $pay);
        }

        return [
            'id' => $ids,
            'value' => $totalValue
        ];
    }

    /**
     * Credit
     *
     * @return array
     */
    private function refund($now)
    {
        $datas = Order::select('id', 'total_price')
            ->with(['payments:id,total_price,order_id'])
            ->order()
            ->whereDate('refund_dp_date', $now)
            ->where('refund_by', Auth::id())
            ->receivedByMe()
            ->get();

        $ids = [];
        $value = 0;
        foreach ($datas as $row) {
            $ids[] = [
                'order_id' => $row->id,
                'order_payment_ids' => $row->payments->pluck('id')->toArray()
            ];

            $value = $row->payments->sum('total_price');
        }

        return [
            'id' => $ids,
            'value' => $value
        ];
    }
}
