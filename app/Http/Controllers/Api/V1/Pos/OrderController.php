<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Management\BranchDiscount;
use App\Models\Management\CustomerDiscount;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Pos\Closing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
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
        $this->middleware('permission:pesanan.lihat', [
            'only' => ['listProduct', 'listPayment', 'checkingClosing']
        ]);
        $this->middleware('permission:pesanan.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:pesanan.tambah', [
            'only' => ['storeCustomer']
        ]);
        $this->middleware('permission:pesanan.tambah|permission:pesanan.lihat', [
            'only' => ['listCategory']
        ]);
        $this->middleware('permission:pesanan.lihat', [
            'only' => ['listCustomer']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $customer = $request->customer_id;
        $data = Product::select('id', 'code', 'name', 'price', 'product_category_id')->with('stocks')->available()->search($request)->orderBy('code')->get();
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
                // if ($discount = BranchDiscount::select('id', 'discount')->where('discount_category', $branch->discount_active)->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                //     if (in_array($value->product_category_id, [1,6])) {
                //         $value->discount_branch_nominal = $discount->discount;
                //     } else {
                //         $value->discount_branch_nominal = $discount->discount / 100 * $value->price;
                //     }
                // } else {
                //     $value->discount_branch_nominal = 0;
                // }

                // /**
                //  * Cek discount expired
                //  */
                // if ($discountExpired = BranchDiscount::select('id', 'discount')->where('discount_category', 'expired')->where('branch_id', $branch_id)->where('product_id', $value->id)->first()) {
                //     if (in_array($value->product_category_id, [1,6])) {
                //         $value->discount_branch_nominal = $discountExpired->discount;
                //     } else {
                //         $value->discount_branch_nominal = $discountExpired->discount / 100 * $value->price;
                //     }
                // }

                $value->discount_branch_nominal = 0;
                $value->discount_customer_nominal = 0;
                $value->total_price = $value->price - $value->discount_branch_nominal;
            }
        }

        return $this->response($data);
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
                if ($value['product_category_id'] == 1 || $value['product_category_id'] == 6) {
                    $value['discount_type'] = 'nominal';
                } else {
                    $value['discount_type'] = 'percentage';
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $closing = Closing::cekClosing();
        if ($closing) {
            return $this->response('Hari ini Anda tidak bisa melakukan transaksi karena sudah melakukan Closing', 'error', 422);
        }

        $data = $this->validate($request, [
            'customer_id' => 'nullable|exists:customers,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'payment_id' => 'required|exists:payment_methods,id',
            'payment_desc' => 'nullable|string',
            'payment_type' => 'required|in:paid,dp,pay-later',
            'pay' => 'required_if:payment_type,paid',
            'pay' => 'required_if:payment_type,dp',
            'note' => 'nullable',
            'date_pickup' => 'required|date',
            'product_id' => 'required|array',
            'product_id.*.id' => 'required|exists:products,id',
            'product_id.*.qty' => 'required|min:0'
        ]);

        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $customer = Customer::find($data['customer_id']);
            $data['customer_name'] = $customer->name;
            $data['customer_phone'] = $customer->phone;
            $data['customer_email'] = $customer->email;
            $data['customer_discount'] = $customer->discount;
        }

        $products = $data['product_id'];

        if (in_array($data['product_category_id'], [14])) {
            $type = 'ingredient';
        } else {
            $type = 'product';
        }

        $payment = PaymentMethod::find($data['payment_id']);
        $data['payment_name'] = $payment->name;

        $auth = Auth::user();
        $data['branch_id'] = $auth->branch_id;

        $data['status_pickup'] = 'new';
        $data['type'] = 'order';
        if ($data['payment_type'] == 'paid') {
            $data['status_payment'] = 'paid';
        } else {
            $data['status_payment'] = 'not-paid';
        }

        $orderId = null;
        $datePickup = $data['date_pickup'];
        $data = DB::connection('mysql')->transaction(function () use ($data, $auth, &$orderId) {
            $model = $this->model->create($data);
            $orderId = $model->id;

            $totalPriceProduct = 0;
            $branch = Branch::select('id', 'discount_active')->find($data['branch_id']);

            foreach ($data['product_id'] as $key => $value) {
                $product = Product::find($value['id']);

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
                    if ($branchDiscount = BranchDiscount::where('branch_id', $auth->branch_id)->where('discount_category', $branch->discount_active)->where('product_id', $product->id)->first()) {
                        if ($branchDiscount->discount_type == 'percentage') {
                            $totalDiscount = $branchDiscount->discount / 100 * $product->price;
                        } else {
                            $totalDiscount = $branchDiscount->discount;
                        }
                    }

                    /**
                     * Cek discount expired
                     */
                    if ($discountExpired = BranchDiscount::select('id', 'discount', 'product_category_id', 'discount_type')->where('discount_category', 'expired')->where('branch_id', $data['branch_id'])->where('product_id', $product->id)->first()) {
                        if ($discountExpired->discount_type == 'percentage') {
                            $totalDiscount = $discountExpired->discount / 100 * $product->price;
                        } else {
                            $totalDiscount = $discountExpired->discount;
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

                // if ($stock = ProductStock::where('product_id', $value['id'])->where('branch_id', $auth->branch_id)->first()) {
                //     $stock->update([
                //         'stock' => $stock->stock - $value['qty']
                //     ]);
                // }
            }

            $model->update([
                'total_price' => $totalPriceProduct
            ]);

            if ($data['payment_type'] == 'paid') {
                $note = 'Lunas';
            } else {
                $note = 'DP';
            }

            if ($data['payment_type'] != 'pay-later') {
                $model->payments()->create([
                    'payment_number' => 1,
                    'payment_id' => $data['payment_id'],
                    'payment_name' => $data['payment_name'],
                    'total_price' => $data['pay'],
                    'note' => $note,
                    'type' => $note == "DP" ? 'dp' : 'paid',
                ]);
            }

            return $model;
        });

        if ($data->total_price <= (int)$data->pay) {
            $data->update([
                'status_payment' => 'paid'
            ]);
        }

        $this->createPo($products, $orderId, $type, $datePickup);

        return $this->response($data);
    }

    /**
     * Integration to PO
     *
     * @param  array  $products
     * @param  int  $orderId
     * @param  int  $type
     * @return void
     */
    private function createPo($products, $orderId, $type, $datePickup)
    {
        if ($type == 'ingredient') {
            $this->createPoIngredient($products, $orderId, $datePickup);
        } else {
            $auth = Auth::user();
            // $branch = Branch::select('id', 'is_production')->where('id', $auth->branch_id)->first();
            $branch = DB::table('branches')->where('id', $auth->branch_id)->first();
            // $a = Log::error('a: ' . json_encode($branch->toArray()));
            if ($branch && $branch->is_production == 1) {
                $this->createPoIngredient($products, $orderId, $datePickup);
            } else {
                $this->createPoProduct($products, $orderId, $datePickup);
            }
        }
    }

    /**
     * Integration to PO Product
     *
     * @param  array  $products
     * @param  int  $orderId
     * @return void
     */
    private function createPoProduct($products, $orderId, $datePickup)
    {
        $availableAt = date('Y-m-d', strtotime('-1 days', strtotime($datePickup)));
        // $model = PoOrderProduct::where('branch_id', Auth::user()->branch_id)->where('available_at', $availableAt)->order()->first();
        // if (!$model) {
        //     $model = PoOrderProduct::create([
        //         'order_id' => $orderId,
        //         'available_at' => $availableAt
        //     ]);
        //     $model->statusLogs()->create(['status' => 'new']);
        // }
        $model = PoOrderProduct::create([
            'order_id' => $orderId,
            'available_at' => $availableAt
        ]);
        $model->statusLogs()->create(['status' => 'new']);

        foreach ($products as $value) {
            $model->details()->create([
                'product_id' => $value['id'],
                'qty' => $value['qty'],
            ]);
        }
    }

    /**
     * Integration to PO Product
     *
     * @param  array  $products
     * @param  int  $orderId
     * @return void
     */
    private function createPoIngredient($products, $orderId, $datePickup)
    {
        $availableAt = date('Y-m-d', strtotime('-2 days', strtotime($datePickup)));
        $model = PoOrderIngredient::where('branch_id', Auth::user()->branch_id)->where('available_at', $availableAt)->first();
        // if (!$model) {
        //     $model = PoOrderIngredient::create([
        //         'order_id' => $orderId,
        //         'available_at' => $availableAt
        //     ]);
        //     $model->statusLogs()->create(['status' => 'new']);
        // }
        $model = PoOrderIngredient::create([
            'order_id' => $orderId,
            'available_at' => $availableAt
        ]);
        $model->statusLogs()->create(['status' => 'new']);

        foreach ($products as $value) {
            $recipes = ProductRecipe::where('product_id', $value['id'])->get();
            foreach ($recipes as $row) {
                $model->details()->create([
                    'product_ingredient_id' => $row->product_ingredient_id,
                    'product_ingredient_unit_id' => $row->product_recipe_unit_id,
                    'qty' => $row->measure,
                ]);
            }
        }
    }
}
