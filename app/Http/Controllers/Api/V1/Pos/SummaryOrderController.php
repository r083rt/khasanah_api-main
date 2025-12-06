<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Management\BranchDiscount;
use App\Models\Management\CustomerDiscount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Pos\Closing;
use App\Models\Pos\OrderPayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SummaryOrderController extends Controller
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
    public function __construct(Order $model, StockService $stockService)
    {
        $this->middleware('permission:summary-pesanan.lihat', [
            'only' => ['index', 'listBranch', 'show']
        ]);
        $this->middleware('permission:summary-pesanan.ubah', [
            'only' => ['update']
        ]);
        $this->model = $model;
        $this->stockService = $stockService;
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
            'status' => 'required|in:all,not-paid,paid,not-pickup,pickup,pickup-tommorow,pickup-now,pickup-not-paid,pickup-expired,order-now'
        ]);

        $model = $this->model->search($request)->branch()->order()->with([
            'createdBy:id,name',
            'branch:id,name,address',
            'category:id,name',
            'products:id,product_name,qty,order_id',
            'customer:id,name',
            'receivedBy:id,name',
            'payments.createdBy:id,name',
            'refundBy:id,name'
        ]);

        if (in_array($data['status'], ['not-paid', 'paid'])) {
            $model = $model->where('status_payment', $data['status'])->whereDate('created_at', '>', Carbon::now()->subDays(30));
        }

        if (in_array($data['status'], ['not-pickup', 'pickup'])) {
            $model = $model->where('status_pickup', $data['status'])->whereDate('created_at', '>', Carbon::now()->subDays(30));
        }

        if ($data['status'] == 'pickup-tommorow') {
            $datetime = new DateTime('tomorrow');
            $model = $model->where('date_pickup', $datetime->format('Y-m-d'));
        }

        if ($data['status'] == 'pickup-now') {
            $datetime = date('Y-m-d');
            $model = $model->where('date_pickup', $datetime);
        }

        if ($data['status'] == 'pickup-not-paid') {
            $model = $model->where('status_pickup', 'done')->where('status_payment', 'not-paid');
        }

        if ($data['status'] == 'pickup-expired') {
            $model = $model->where('date_pickup', '<', date('Y-m-d'));
        }

        if ($data['status'] == 'order-now') {
            $model = $model->whereDate('created_at', date('Y-m-d'));
        }

        if ($data['status'] == 'all') {
            $model = $model->whereDate('created_at', '>', Carbon::now()->subDays(30));
        }

        if (Auth::user()->branch_id == 1) {
            if (isset($data['branch_id']) && $data['branch_id']) {
                $model = $model->where('branch_id', $data['branch_id']);
            }
        } else {
            $model = $model->where('branch_id', Auth::user()->branch_id);
        }

        $model = $model->where('status', '!=', 'completed')->whereDate('created_at', '>=', $data['start_date'])->whereDate('created_at', '<=', $data['end_date'])->sort($request)->get();
        return $this->response($model);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listCategory(Request $request)
    {
        $data = ProductCategory::select('id', 'name')->search($request)->orderBy('name')->get();
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

    /**
     * Display a listing of the resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function refundDp($id)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $model = $this->model->select('id', 'status')->branch()->order()->findOrFail($id);

        if ($model->status != 'canceled') {
            $model->update([
                'refund_dp_date' => date('Y-m-d H:i:s'),
                'refund_by' => Auth::id(),
                'status' => 'canceled'
            ]);
        }


        return $this->response(true);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $customer = isset($request->customer_id) ? $request->customer_id : null;
        $data = Product::select('id', 'code', 'name', 'price', 'product_category_id')->with('stocks')->available()->search($request)->orderBy('name')->get();
        $branch_id = Auth::user()->branch_id;
        $branch = Branch::select('id', 'discount_active')->find($branch_id);

        foreach ($data as $key => $value) {
            if ($customer) {
                if ($discount = CustomerDiscount::select('id', 'discount')->where('customer_id', $customer)->where('product_category_id', $value->product_category_id)->where('product_id', $value->id)->first()) {
                    if (in_array($value->product_category_id, [1,6])) {
                        $value->discount_customer_nominal = $discount->discount;
                    } else {
                        $value->discount_customer_nominal = $discount->discount / 100 * $value->price;
                    }
                } else {
                    $value->discount_customer_nominal = 0;
                }

                $value->discount_branch_nominal = 0;
                $value->total_price = $value->price - $value->discount_customer_nominal;
            } else {
                if ($discount = BranchDiscount::select('id', 'discount')->where('discount_category', $branch->discount_active)->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                    if (in_array($value->product_category_id, [1,6])) {
                        $value->discount_branch_nominal = $discount->discount;
                    } else {
                        $value->discount_branch_nominal = $discount->discount / 100 * $value->price;
                    }
                } else {
                    $value->discount_branch_nominal = 0;
                }

                /**
                 * Cek discount expired
                 */
                if ($discountExpired = BranchDiscount::select('id', 'discount')->where('discount_category', 'expired')->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                    if (in_array($value->product_category_id, [1,6])) {
                        $value->discount_branch_nominal = $discountExpired->discount;
                    } else {
                        $value->discount_branch_nominal = $discountExpired->discount / 100 * $value->price;
                    }
                }

                $value->discount_customer_nominal = 0;
                $value->total_price = $value->price - $value->discount_branch_nominal;
            }
        }

        return $this->response($data);
    }

    /**
     * Update Status
     *
     * @param  collection $model
     * @return collection
     */
    private function updateStatus($model, $auth)
    {
        if ($model->received_date) {
            $model->update([
                'status' => 'completed',
                'status_pickup' => 'done',
            ]);
        } else {
            $model->update([
                'status' => 'completed',
                'status_pickup' => 'done',
                'received_date' => date('Y-m-d H:i:s'),
                'received_by' => $auth->id
            ]);
        }

        return $model;
    }

    /**
     * Update Stock
     *
     * @param integer $productId
     * @param integer $stock
     * @param model $auth
     */
    private function updateStock($productId, $stock, $auth, $closingId)
    {
        $stockOld = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $productId)->first();
        if ($stockOld) {
            $oldStock = $stockOld->stock;
            $stockOld->update([
                'stock' => $oldStock - $stock
            ]);

            $this->stockService->createStockLog([
                'branch_id' => $auth->branch_id,
                'product_id' => $productId,
                'stock' => $stock * -1,
                'stock_old' => $oldStock,
                'from' => 'Pesanan',
                'table_reference' => 'orders',
                'table_id' => $closingId,
            ]);

            /**
             * Clear cache
             */
            Cache::flush();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deletePayment($id)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $order = OrderPayment::findOrFail($id);
        $this->model->whereHas('payments', function ($query) use ($id) {
            $query->where('id', $id);
        })->branch()->firstOrFail();

        if ($order->payment_number == 1) {
            return $this->response('Data tidak dapat dihapus', 'error', 422);
        }

        if ($order->created_by != Auth::id()) {
            return $this->response('Data tidak dapat dihapus', 'error', 422);
        }

        $data = $order->delete();

        return $this->response($data ? true : false);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteProduct($id)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $auth = Auth::user();
        $order = OrderProduct::findOrFail($id);
        $this->model->whereHas('products', function ($query) use ($id) {
            $query->where('id', $id);
        })->branch()->firstOrFail();

        $qty = $order->qty;
        $product_id = $order->product_id;
        $order_id = $order->order_id;
        $data = $order->delete();

        /**
         * Return stock
         */
        // if ($stock = ProductStock::where('product_id', $product_id)->where('branch_id', $auth->branch_id)->first()) {
        //     $oldStock = $stock->stock;
        //     $stock->update([
        //         'stock' => $oldStock + $qty
        //     ]);

        //     $this->stockService->createStockLog([
        //         'branch_id' => $auth->branch_id,
        //         'product_id' => $product_id,
        //         'stock' => $qty,
        //         'stock_old' => $oldStock,
        //         'from' => 'Pesanan hapus produk',
        //         'table_reference' => 'orders',
        //         'table_id' => $id,
        //     ]);
        // }

        /**
         * Update total price order
         */
        $order = Order::find($order_id);
        $totalPrice = OrderProduct::where('order_id', $order_id)->sum('total_price');
        $order->update([
            'total_price' => $totalPrice
        ]);

        return $this->response($data ? true : false);
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
            return $this->model->whereIn('id', $data['id'])->order()->branch()->delete();
        });

        return $this->response($data ? true : false);
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
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $auth = Auth::user();

        $model = Order::select('*')->branch()->findOrFail($id);
        $orderProductIds = OrderProduct::select('id')->where('order_id', $id)->get()->implode('id', ',');

        if ($model->status_payment == 'paid') {
            $data = $this->validate($request, [
                'type' => 'required|in:finish'
            ], []);
            $isPaid = true;
        } else {
            $data = $this->validate($request, [
                'type' => 'required|in:payment,finish',
                'payment_type' => 'required|in:repayment-today,order-payment-taken,payment-before-taken,payment-after-taken',
                'pay' => 'nullable|integer|min:0',
                'payment_id' => 'nullable|exists:payment_methods,id',
                'payment_desc' => 'nullable',
                'order_products' => 'required|array',
                'order_products.*.id' => 'required|integer|in: ' . $orderProductIds,
                'order_products.*.qty' => 'required|integer',
            ], [
                'payment_type.required' => 'Tipe Pembayaran wajib diisi.',
                'pay.min' => 'Jumlah bayar harus lebih dari 0.',
                'pay.required' => 'Jumlah bayar wajib diisi.',
                'payment_id.required' => 'Jenis pembayaran wajib diisi.',
                'order_products.*.id.required' => 'Product wajib diisi.',
                'order_products.*.id.in' => 'Product tidak valid.',
                'order_products.*.id.exists' => 'Product dipilih tidak valid.',
                'order_products.*.qty.required' => 'Jumlah Product wajib diisi.',
            ]);
            $isPaid = false;
        }

        if ($model->customer_id) {
            $data['customer_id'] = $model->customer_id;
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $auth, $model, $isPaid) {
            if ($isPaid) {
                $products = OrderProduct::select('product_id', 'qty')->where('order_id', $model->id)->get();
                if (is_null($model->received_date)) {
                    foreach ($products as $value) {
                        $this->updateStock($value->product_id, $value->qty, $auth, $model->id);
                    }
                }
                $this->updateStatus($model, $auth);
            } else {
                $orderId = $model->id;
                $totalAllPrice = 0;
                foreach ($data['order_products'] as $key => $value) {
                    $orderProduct = OrderProduct::select('id', 'product_id', 'product_price')->find($value['id']);
                    $oldQty = $orderProduct->qty;
                    $product = Product::find($orderProduct->product_id);

                    $totalDiscount = 0;
                    if (isset($data['customer_id']) && $data['customer_id'] != '') {
                        if ($customerDiscount = CustomerDiscount::where('customer_id', $data['customer_id'])->where('product_category_id', $product->product_category_id)->where('product_id', $product->id)->first()) {
                            if ($customerDiscount->discount_type == 'percentage') {
                                $totalDiscount = $customerDiscount->discount / 100 * $product->price;
                            } else {
                                $totalDiscount = $customerDiscount->discount;
                            }
                        }
                    } else {
                        if ($branchDiscount = BranchDiscount::where('branch_id', $auth->branch_id)->where('product_id', $product->id)->first()) {
                            if ($branchDiscount->discount_type == 'percentage') {
                                $totalDiscount = $branchDiscount->discount / 100 * $product->price;
                            } else {
                                $totalDiscount = $branchDiscount->discount;
                            }

                            /**
                             * Cek discount expired
                             */
                            if ($discountExpired = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $auth->branch_id)->where('product_id', $product->id)->first()) {
                                if ($discountExpired->discount_type == 'percentage') {
                                    $totalDiscount = $discountExpired->discount / 100 * $product->price;
                                } else {
                                    $totalDiscount = $discountExpired->discount;
                                }
                            }
                        }
                    }
                    $totalDiscount = $totalDiscount * $value['qty'];
                    $total_price = ($value['qty'] * $orderProduct->product_price) - $totalDiscount;

                    $orderProduct->update([
                        'qty' => $value['qty'],
                        'discount' => $totalDiscount,
                        'total_price' => $total_price,
                    ]);
                    $totalAllPrice = $totalAllPrice + $total_price;

                    /**
                     * update stock
                     */
                    // if ($data['type'] == 'finish' || $data['payment_type'] == 'payment-after-taken') {
                    //     $stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $orderProduct->product_id)->first();
                    //     if ($stock) {
                    //         $stock->update([
                    //             'stock' => ($stock->stock + $oldQty) - $value['qty']
                    //         ]);
                    //     }
                    // }
                }

                /**
                 * payment
                 */
                if ($data['pay'] >= 0) {
                    if ($payment = OrderPayment::select('payment_number')->where('order_id', $model->id)->orderBy('payment_number', 'desc')->first()) {
                        $paymentNumber = $payment->payment_number + 1;
                    } else {
                        $paymentNumber = 1;
                    }
                    $payment = PaymentMethod::find($data['payment_id']);

                    $model->payments()->create([
                        'payment_number' => $paymentNumber,
                        'payment_id' => $data['payment_id'],
                        'payment_desc' => $data['payment_desc'],
                        'payment_name' => $payment->name,
                        'total_price' => $data['pay'],
                        'type' => $data['payment_type']
                    ]);
                }

                $total_price = OrderPayment::where('order_id', $orderId)->sum('total_price');
                $status = 'not-paid';
                if ($total_price >= $totalAllPrice) {
                    $status = 'paid';
                }

                $model->update([
                    'total_price' => $totalAllPrice,
                    'status_payment' => $status,
                ]);

                /**
                 * Update status & stock
                 */
                $products = OrderProduct::select('product_id', 'qty')->where('order_id', $model->id)->get();
                if ($data['type'] == 'finish') {
                    if (is_null($model->received_date)) {
                        foreach ($products as $value) {
                            $this->updateStock($value->product_id, $value->qty, $auth, $model->id);
                        }
                    }

                    $this->updateStatus($model, $auth);
                } else {
                    if ($data['payment_type'] == 'repayment-today') {
                        if (is_null($model->received_date)) {
                            foreach ($products as $value) {
                                $this->updateStock($value->product_id, $value->qty, $auth, $model->id);
                            }

                            $model->update([
                                'received_date' => date('Y-m-d H:i:s'),
                                'received_by' => Auth::id(),
                                'status_pickup' => 'done'
                            ]);
                        }
                    }
                }
            }

            return true;
        });

        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $data = $this->validate($request, [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|min:0'
        ]);

        $model = Order::select('id', 'total_price', 'customer_id')->branch()->findOrFail($id);
        $auth = Auth::user();

        if ($model->customer_id) {
            $data['customer_id'] = $model->customer_id;
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $auth) {
            $product = Product::find($data['product_id']);

            $totalDiscount = 0;
            if (isset($data['customer_id']) && $data['customer_id'] != '') {
                if ($customerDiscount = CustomerDiscount::where('customer_id', $data['customer_id'])->where('product_category_id', $product->product_category_id)->where('product_id', $product->id)->first()) {
                    if ($customerDiscount->discount_type == 'percentage') {
                        $totalDiscount = $customerDiscount->discount / 100 * $product->price;
                    } else {
                        $totalDiscount = $customerDiscount->discount;
                    }
                }
            } else {
                if ($branchDiscount = BranchDiscount::where('branch_id', $auth->branch_id)->where('product_id', $product->id)->first()) {
                    if ($branchDiscount->discount_type == 'percentage') {
                        $totalDiscount = $branchDiscount->discount / 100 * $product->price;
                    } else {
                        $totalDiscount = $branchDiscount->discount;
                    }
                }

                /**
                 * Cek discount expired
                 */
                if ($discountExpired = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $auth->branch_id)->where('product_id', $product->id)->first()) {
                    if ($discountExpired->discount_type == 'percentage') {
                        $totalDiscount = $discountExpired->discount / 100 * $product->price;
                    } else {
                        $totalDiscount = $discountExpired->discount;
                    }
                }
            }
            $totalDiscount = $totalDiscount * $data['qty'];
            $totalPrice = ($data['qty'] * $product->price) - $totalDiscount;

            $model->products()->create([
                'product_id' => $data['product_id'],
                'product_name' => $product->name,
                'product_code' => $product->code,
                'product_price' => $product->price,
                'discount' => $totalDiscount,
                'qty' => $data['qty'],
                'total_price' => $totalPrice
            ]);

            // if ($stock = ProductStock::where('product_id', $data['product_id'])->where('branch_id', $auth->branch_id)->first()) {
            //     $stock->update([
            //         'stock' => $stock->stock - $data['qty']
            //     ]);
            // }

            $model->update([
                'total_price' => $model->total_price + $totalPrice,
                'status_payment' => 'not-paid'
            ]);

            return $model;
        });

        return $this->response(true);
    }
}
