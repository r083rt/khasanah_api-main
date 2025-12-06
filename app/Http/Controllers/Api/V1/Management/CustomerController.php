<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
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
    public function __construct(Customer $model)
    {
        $this->middleware('permission:customer.lihat|customer.show', [
            'only' => ['index', 'show', 'listCategory']
        ]);
        $this->middleware('permission:customer.tambah', [
            'only' => ['store', 'listCategory']
        ]);
        $this->middleware('permission:customer.ubah', [
            'only' => ['update', 'listCategory']
        ]);
        $this->middleware('permission:customer.hapus', [
            'only' => ['destroy']
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
        $data = $this->model->with(['discounts', 'discounts.user:id,name', 'discounts.logs:id,customer_discount_id,discount_old,discount_new'])->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
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
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name', 'code', 'price')->where('product_category_id', $request->product_category_id)->search($request)->orderBy('name')->get();
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
        $data = $this->validate($request, [
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'category' => 'required|in:general,reseller',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'discounts' => 'nullable|array',
            'discounts.*.product_category_id' => 'required|integer',
            'discounts.*.product_id' => 'required|integer',
            'discounts.*.discount' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            foreach ($data['discounts'] as $value) {
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->with('discounts:id,customer_id,product_category_id,discount_type,discount,product_id')->findOrFail($id);
        return $this->response($model);
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
        $data = $this->validate($request, [
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'category' => 'required|in:general,reseller',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'discounts' => 'nullable|array',
            'discounts.*.product_category_id' => 'required|integer',
            'discounts.*.product_id' => 'required|integer',
            'discounts.*.discount' => 'required|integer',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->discounts()->delete();
            foreach ($data['discounts'] as $value) {
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

        return $this->response($model);
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
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        /**
         * Clear cache
         */
        Cache::forget('customer');

        return $this->response($data ? true : false);
    }
}
