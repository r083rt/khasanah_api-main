<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\CreateStockLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Management\BranchDiscount;
use App\Models\Management\CustomerDiscount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Pos\Closing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Services\Inventory\ProductService;
use App\Services\Inventory\StockService;
use App\Services\Management\CustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $stockService;

    protected $productService;

    protected $customerService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(Order $model, StockService $stockService, ProductService $productService, CustomerService $customerService)
    {
        $this->middleware('permission:kasir.lihat', [
            'only' => ['listProduct', 'checkingClosing']
        ]);
        $this->middleware('permission:kasir.tambah', [
            'only' => ['store', 'updateCart']
        ]);
        $this->middleware('permission:history-kasir.lihat', [
            'only' => ['history']
        ]);
        $this->middleware('permission:history-kasir.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:history-kasir.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:kasir.tambah|permission:history-kasir.tambah|permission:history-kasir.ubah', [
            'only' => ['storeCustomer']
        ]);
        $this->middleware('permission:kasir.tambah|permission:history-kasir.lihat|permission:history-kasir.ubah', [
            'only' => ['listCategory']
        ]);
        $this->model = $model;
        $this->stockService = $stockService;
        $this->productService = $productService;
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $customer = $request->has('customer_id');
        $branch_id = Auth::user()->branch_id;

        $keyAll = 'product-all-cashier-online-' . $branch_id;
        // if (!Cache::has($keyAll)) {
        $branch = Branch::select('id', 'discount_active')->find($branch_id);

        $data = $this->productService->getAll(false, $branch_id);
        $datas = [];
        foreach ($data as $value) {
            $value = (object) $value;
            $stocks = $this->stockService->getStock($value->id, $branch_id);

            if ($customer) {
                if ($discount = CustomerDiscount::select('id', 'discount', 'discount_type')->where('customer_id', $customer)->where('product_category_id', $value->product_category_id)->where('product_id', $value->id)->first()) {
                    if ($discount->discount_type == 'percentage') {
                        $discount_customer_nominal = $discount->discount / 100 * $value->price;
                    } else {
                        $discount_customer_nominal = $discount->discount;
                    }
                } else {
                    $discount_customer_nominal = 0;
                }

                $discount_branch_nominal = 0;
                $total_price = $value->price - $discount_customer_nominal;
            } else {
                if ($discount = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', $branch->discount_active)->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                    if ($discount->discount_type == 'percentage') {
                        $discount_branch_nominal = $discount->discount / 100 * $value->price;
                    } else {
                        $discount_branch_nominal = $discount->discount;
                    }
                } else {
                    $discount_branch_nominal = 0;
                }

                /**
                 * Cek discount expired
                 */
                if ($discountExpired = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                    if ($discountExpired->discount_type == 'percentage') {
                        $discount_branch_nominal = $discountExpired->discount / 100 * $value->price;
                    } else {
                        $discount_branch_nominal = $discountExpired->discount;
                    }
                }

                $discount_customer_nominal = 0;
                $total_price = $value->price - $discount_branch_nominal;
            }

            $datas[] = [
                'id' => $value->id,
                'code' => $value->code,
                'name' => $value->name,
                'price' => $value->price,
                'product_category_id' => $value->product_category_id,
                'stock' => $stocks ? $stocks['stock'] : 0,
                'discount_customer_nominal' => $discount_customer_nominal,
                'discount_branch_nominal' => $discount_branch_nominal,
                'total_price' => $total_price,
            ];
        }
            // Cache::put($keyAll, $datas, 86400);
        // } else {
        //     $datas = Cache::get($keyAll);
        // }

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listCustomerAndProduct(Request $request)
    {
        $branch_id = Auth::user()->branch_id;
        $keyAll = 'product-all-cashier-' . $branch_id;
        // if (!Cache::has($keyAll)) {
        $customers = $this->customerService->getAll(['discounts:id,product_category_id,product_id,discount,discount_type', 'discounts.product:id,price'], 'discounts');
        $datas = [];
        if ($customers) {
            $datas = [];
            foreach ($customers as $row) {
                $products = [];
                foreach ($row['discounts'] as $value) {
                    $product = $value['product'];
                    if ($product) {
                        if ($value['discount_type'] == 'percentage') {
                            $discount_customer_nominal = $value['discount'] / 100 * $product['price'];
                        } else {
                            $discount_customer_nominal = $value['discount'];
                        }

                        $total_price = $product['price'] - $discount_customer_nominal;

                        if ($total_price != $product['price']) {
                            $products[] = [
                                'id' => $product['id'],
                                'price' => $product['price'],
                                'discount_customer_nominal' => $discount_customer_nominal,
                                'total_price' => $total_price
                            ];
                        }
                    }
                }

                if (!empty($products)) {
                    $datas[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'products' => $products,
                    ];
                }
            }

            // Cache::put($keyAll, $datas, 86400);
        }
        // } else {
        //     $datas = Cache::get($keyAll);
        // }

        return $this->response($datas);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listPayment(Request $request)
    {
        $data = PaymentMethod::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
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
     * @return \Illuminate\Http\Response
     */
    public function listCustomer(Request $request)
    {
        $data = Customer::select('id', 'name', 'phone')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Checking Closing
     *
     * @return \Illuminate\Http\Response
     */
    public function checkingClosing()
    {
        $cek = Closing::cekClosing();
        return $this->response($cek);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $closing = Closing::cekClosing();
        // if ($closing) {
        //     return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        // }

        $data = $this->validate($request, [
            'customer_id' => 'nullable',
            'payment_id' => 'required',
            'payment_desc' => 'nullable|string',
            'pay' => 'required|numeric',
            'product_id' => 'required|array',
            'product_id.*.id' => 'required',
            'product_id.*.qty' => 'required|min:1',
            'uuid' => 'required',
        ]);

        // $order = Order::cekDuplicate($data['uuid']);
        // if ($order) {
        //     return $this->response('Anda sudah melakukan transaksi yang sama. Silahkan lakukan transaksi lain', 'error', 422);
        // }

        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $customer = Customer::select('name', 'phone', 'email')->find($data['customer_id']);
            if ($customer) {
                $data['customer_name'] = $customer->name;
                $data['customer_phone'] = $customer->phone;
                $data['customer_email'] = $customer->email;
            }
        }

        $payment = PaymentMethod::select('name')->find($data['payment_id']);
        $data['payment_name'] = $payment?->name;

        $auth = Auth::user();
        $data['branch_id'] = $auth->branch_id;

        $data['status_pickup'] = 'done';
        $data['status'] = 'completed';
        $data['type'] = 'cashier';

        $model = $this->model->create($data);

        $totalPriceProduct = 0;
        $discountType = null;
        $branch = Branch::select('id', 'discount_active')->find($data['branch_id']);

        $product_ids = [];
        foreach ($data['product_id'] as $value) {
            $product_ids[] = $value['id'];
        }

        $products = Product::select('id', 'product_category_id', 'price', 'name', 'code')->whereIn('id', $product_ids)->get();
        foreach ($data['product_id'] as $value) {
            $product = $products->where('id', $value['id'])->first();

            $totalDiscount = 0;
            if (isset($data['customer_id']) && $data['customer_id'] != '') {
                if ($customerDiscount = CustomerDiscount::select('discount_type', 'discount')->where('customer_id', $data['customer_id'])->where('product_category_id', $product->product_category_id)->first()) {
                    if ($customerDiscount->discount_type == 'percentage') {
                        $totalDiscount = $customerDiscount->discount / 100 * $product->price;
                        $discountType = 'customer';
                    } else {
                        $totalDiscount = $customerDiscount->discount;
                        $discountType = 'customer';
                    }
                }
            } else {
                if ($branchDiscount = BranchDiscount::select('discount_type', 'discount')->where('branch_id', $auth->branch_id)->where('discount_category', $branch->discount_active)->where('product_id', $product->id)->first()) {
                    if ($branchDiscount->discount_type == 'percentage') {
                        $totalDiscount = $branchDiscount->discount / 100 * $product->price;
                        $discountType = 'branch';
                    } else {
                        $totalDiscount = $branchDiscount->discount;
                        $discountType = 'branch';
                    }
                }

                /**
                 * Cek discount expired
                 */
                if ($discountExpired = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $auth->branch_id)->where('product_id', $product->id)->first()) {
                    if ($discountExpired->discount_type == 'percentage') {
                        $totalDiscount = $discountExpired->discount / 100 * $value->price;
                        $discountType = 'branch';
                    } else {
                        $totalDiscount = $discountExpired->discount;
                        $discountType = 'branch';
                    }
                }
            }
            $totalDiscount = $totalDiscount * $value['qty'];
            $totalPrice = ($value['qty'] * $product->price) - $totalDiscount;

            $model->products()->create([
                'product_id' => $value['id'],
                'product_name' => $product->name,
                'product_code' => $product->code,
                'product_price' => $product->price,
                'discount' => $totalDiscount,
                'qty' => $value['qty'],
                'total_price' => $totalPrice
            ]);
            $totalPriceProduct = $totalPriceProduct + $totalPrice;

            $allData = [
                'branch_id' => $auth->branch_id,
                'product_id' => $value['id'],
                'stock' => $value['qty'] * -1,
                'from' => 'Penjualan',
                'table_reference' => 'orders',
                'table_id' => $model->id,
                'user_id' => $auth->id
            ];

            dispatch(new CreateStockLog($allData));

            // if ($stock = ProductStock::where('product_id', $value['id'])->where('branch_id', $auth->branch_id)->first()) {
            //     $oldStock = $stock->stock;
            //     $stock->update([
            //         'stock' => $oldStock - $value['qty']
            //     ]);


            //     $this->stockService->createStockLog([
            //         'branch_id' => $auth->branch_id,
            //         'product_id' => $value['id'],
            //         'stock' => $value['qty'] * -1,
            //         'from' => 'Penjualan',
            //         'table_reference' => 'orders',
            //         'table_id' => $model->id,
            //         'user_id' => $auth->id
            //     ]);
            // }

            /**
             * Clear cache
             */
            Cache::forget('product-stock-' . $value['id'] . '-' . $data['branch_id']);
        }

        Cache::forget('product-all-cashier-online-' . $data['branch_id']);
        Cache::forget('product-all-cashier-' . $data['branch_id']);
        Cache::forget('discount-cashier-' . $data['branch_id']);

        $model->update([
            'total_price' => $totalPriceProduct,
            'discount_type' => $discountType,
        ]);

        return $this->response($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function updateCart(Request $request)
    {
        $data = $this->validate($request, [
            'customer_id' => 'nullable|exists:customers,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required|integer'
        ]);

        $branch_id = Auth::user()->branch_id;
        $branch = Branch::select('id', 'discount_active')->find($branch_id);

        $products = [];
        foreach ($data['products'] as $key => $value) {
            $dataProduct = Product::select('id', 'code', 'name', 'price', 'product_category_id')->with('stocks')->where('id', $value['product_id'])->available()->search($request)->orderBy('name')->first();
            $dataProduct['qty'] = $value['qty'];

            if ($data['customer_id']) {
                if ($discount = CustomerDiscount::select('id', 'discount', 'discount_type')->where('customer_id', $data['customer_id'])->where('product_category_id', $dataProduct->product_category_id)->where('product_id', $dataProduct->id)->first()) {
                    if ($discount->discount_type == 'percentage') {
                        $dataProduct->discount_customer_nominal = $discount->discount / 100 * $dataProduct->price;
                    } else {
                        $dataProduct->discount_customer_nominal = $discount->discount;
                    }
                } else {
                    $dataProduct->discount_customer_nominal = 0;
                }

                $dataProduct->discount_branch_nominal = 0;
                $dataProduct->discount_customer_nominal = $dataProduct->discount_customer_nominal;
                $dataProduct->total_price = $dataProduct->price - $dataProduct->discount_customer_nominal;
            } else {
                if ($discount = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', $branch->discount_active)->where('branch_id', $branch_id)->where('product_id', $dataProduct->id)->first()) {
                    if ($discount->discount_type == 'percentage') {
                        $dataProduct->discount_branch_nominal = $discount->discount / 100 * $dataProduct->price;
                    } else {
                        $dataProduct->discount_branch_nominal = $discount->discount;
                    }
                } else {
                    $dataProduct->discount_branch_nominal = 0;
                }

                /**
                 * Cek discount expired
                 */
                if ($discountExpired = BranchDiscount::select('id', 'discount', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $branch_id)->where('product_id', $dataProduct->id)->first()) {
                    if ($discountExpired->discount_type == 'percentage') {
                        $dataProduct->discount_branch_nominal = $discountExpired->discount / 100 * $dataProduct->price;
                    } else {
                        $dataProduct->discount_branch_nominal = $discountExpired->discount;
                    }
                }

                $dataProduct->discount_customer_nominal = 0;
                $dataProduct->total_price = $dataProduct->price - $dataProduct->discount_branch_nominal;
            }

            $products[] = $dataProduct;
        }

        return $this->response($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeCustomer(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'category' => 'required|in:general,reseller',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'discounts' => 'nullable|array',
            'discounts.*.product_category_id' => 'integer',
            'discounts.*.discount' => 'integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = Customer::create($data);
            foreach ($data['discounts'] as $key => $value) {
                $value['created_by'] =  Auth::id();
                if (in_array($value['product_category_id'], config('management.branch_discount_percentage'))) {
                    $value['discount_type'] = 'percentage';
                } else {
                    $value['discount_type'] = 'nominal';
                }
                $discount = $model->discounts()->create($value);
                $discount->logs()->create([
                    'discount_old' => null,
                    'discount_new' => $value['discount'],
                    'created_by' => $value['created_by']
                ]);
            }

            /**
             * Clear cache
             */
            Cache::forget('customer');

            return $model;
        });

        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function history(Request $request)
    {
        $data = $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'customer_id' => 'nullable|integer',
        ]);

        $model = Order::with(['products', 'createdBy:id,name', 'branch:id,name'])
            ->cashier()
            ->whereDate('created_at', '>=', $data['start_date'])
            ->whereDate('created_at', '<=', $data['end_date'])
            ->where('branch_id', '=', Auth::user()->branch_id);

        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $model = $model->where('customer_id', $data['customer_id']);
        }

        $model = $model->search($request)->sort($request)->paginate($this->perPage($model));

        foreach ($model->items() as $key => $value) {
            $value->products_count = $value->products->sum('qty');
        }

        return $this->response($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function update(Request $request, $id)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $auth = Auth::user();

        $data = $this->validate($request, [
            'order_products' => 'required|array',
            'order_products.*.id' => 'required|integer|exists:order_products',
            'order_products.*.qty' => 'required|integer',
        ], [
            'order_products.*.id.required' => 'Product wajib diisi.',
            'order_products.*.id.exists' => 'Product dipilih tidak valid.',
            'order_products.*.qty.required' => 'Jumlah Product wajib diisi.',
        ]);

        $model = Order::select('id')->branch()->findOrFail($id);

        foreach ($data['order_products'] as $value) {
            $stock = 0;
            $product = OrderProduct::find($value['id']);
            $oldQty = $product->qty;
            if ($product = ProductStock::where('product_id', $product->product_id)->where('branch_id', $auth->branch_id)->first()) {
                $stock = $product->stock;
            }

            $lastStock = ($stock + $oldQty) - $value['qty'];
            if ($lastStock < 0) {
                $product = Product::select('name')->find($product->product_id);
                return $this->response('Stok produk ' . $product->name . ' tidak mencukupi. Sisa tersedia: ' . $stock, 'error', 422);
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $auth, $model) {
            $totalPrice = 0;
            foreach ($data['order_products'] as $key => $value) {
                $product = OrderProduct::find($value['id']);
                $oldQty = $product->qty;

                $discountItem = $product->discount / $product->qty;
                $discount = $discountItem * $value['qty'];
                $total_price = ($product->product_price  * $value['qty']) - $discount;
                $product->update([
                    'discount' => $discount,
                    'qty' => $value['qty'],
                    'total_price' => $total_price
                ]);

                /**
                 * Stock
                 */
                $stock = ProductStock::where('branch_id', $auth->branch_id)->where('product_id', $product->product_id)->first();
                $oldStock = $stock->stock;
                $stock->update([
                    'stock' => ($oldStock + $oldQty) - $value['qty']
                ]);
                $this->stockService->createStockLog([
                    'branch_id' => $auth->branch_id,
                    'product_id' => $product->product_id,
                    'stock' => $value['qty'] * -1,
                    'stock_old' => $$oldStock,
                    'from' => 'Penjualan',
                    'table_reference' => 'orders',
                    'table_id' => $model->id
                ]);

                $totalPrice = $totalPrice + $total_price;
            }

            $model->update([
                'total_price' => $totalPrice
            ]);

            return true;
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
            'id.*' => 'required|exists:order_products,id',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->where('type', 'cashier')->delete();
        });

        return $this->response($data ? true : false);
    }
}
