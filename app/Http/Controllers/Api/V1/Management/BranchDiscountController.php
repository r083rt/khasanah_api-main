<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Management\BranchDiscount;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchDiscountController extends Controller
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
    public function __construct(BranchDiscount $model)
    {
        $this->middleware('permission:cabang-diskon.lihat|cabang-diskon.show', [
            'only' => ['index', 'show', 'listCategory', 'listProduct']
        ]);
        $this->middleware('permission:cabang-diskon.tambah', [
            'only' => ['store', 'listCategory', 'listProduct']
        ]);
        $this->middleware('permission:cabang-diskon.ubah', [
            'only' => ['update', 'listCategory', 'listProduct']
        ]);
        $this->middleware('permission:cabang-diskon.hapus', [
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
        $data = Branch::select('id', 'name', 'discount_active')->with(['discounts.category:id,name', 'discounts.user:id,name', 'discounts.product:id,name'])->whereHas('discounts', function ($query) {
            $query->select('id', 'branch_id', 'product_category_id');
        })->branch()->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
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
        $data = Branch::select('id', 'name')->branch()->search($request)->orderBy('name')->get();
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
            'branch_id' => 'required|exists:branches,id',
            'discount_active' => 'required|in:store,promo',
            'discount_promos'  => 'nullable|array',
            'discount_promos.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_promos.*.product_id' => 'required|exists:products,id',
            'discount_promos.*.discount' => 'required|integer',
            'discount_stores'  => 'nullable|array',
            'discount_stores.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_stores.*.product_id' => 'required|exists:products,id',
            'discount_stores.*.discount' => 'required|integer',
            'discount_expired'  => 'nullable|array',
            'discount_expired.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_expired.*.product_id' => 'required|exists:products,id',
            'discount_expired.*.discount' => 'required|integer',
        ]);

        $cek = BranchDiscount::where('branch_id', $data['branch_id'])->count();
        if ($cek > 0) {
            return $this->response('Data diskon pada branch tersebut sudah tersedia. Silahkan gunakan menu edit pada daftar dibawah.', 'error', 422);
        }

        if (isset($data['discount_promos'])) {
            $cek = $this->cekValidation($data['discount_promos']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        if (isset($data['discount_stores'])) {
            $cek = $this->cekValidation($data['discount_stores']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        if (isset($data['discount_expired'])) {
            $cek = $this->cekValidation($data['discount_expired']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $id = Auth::id();
            $branch = Branch::find($data['branch_id']);
            $branch->update([
                'discount_active' => $data['discount_active']
            ]);

            if (isset($data['discount_promos'])) {
                foreach ($data['discount_promos'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'promo';
                    $model = $this->model->create($value);
                }
            }

            if (isset($data['discount_stores'])) {
                foreach ($data['discount_stores'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'store';
                    $model = $this->model->create($value);
                }
            }

            if (isset($data['discount_expired'])) {
                foreach ($data['discount_expired'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'expired';
                    $model = $this->model->create($value);
                }
            }

            /**
             * Clear cache
             */
            Cache::flush();

            return $model;
        });

        return $this->response(true);
    }

    /**
     * Discount type
     *
     * @param  array  $value
     * @param  array  $data
     * @param  int  $id
     */
    private function discountType($value, $data, $id)
    {
        if (in_array($value['product_category_id'], config('management.branch_discount_percentage'))) {
            $value['discount_type'] = 'percentage';
        } else {
            $value['discount_type'] = 'nominal';
        }
        $value['branch_id'] = $data['branch_id'];
        $value['created_by'] =  $id;

        return $value;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = Branch::select('id', 'name', 'discount_active')->with(['discounts.category:id,name', 'discounts.user:id,name', 'discounts.product:id,name'])->branch()->findOrFail($id);
        return $this->response($model);
    }

    /**
     * Cek Validation input data discount
     */
    private function cekValidation($data)
    {
        $productIds = [];
        $duplicate = false;
        foreach ($data as $value) {
            if (in_array($value['product_id'], $productIds)) {
                $duplicate = true;
            }
            $productIds[] = $value['product_id'];
        }

        return $duplicate;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = $this->validate($request, [
            'branch_id' => 'required|exists:branches,id',
            'discount_active' => 'required|in:store,promo',
            'discount_promos'  => 'nullable|array',
            'discount_promos.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_promos.*.product_id' => 'required|exists:products,id',
            'discount_promos.*.discount' => 'required|integer',
            'discount_stores'  => 'nullable|array',
            'discount_stores.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_stores.*.product_id' => 'required|exists:products,id',
            'discount_stores.*.discount' => 'required|integer',
            'discount_expired'  => 'nullable|array',
            'discount_expired.*.product_category_id' => 'required|exists:product_categories,id',
            'discount_expired.*.product_id' => 'required|exists:products,id',
            'discount_expired.*.discount' => 'required|integer',
        ]);

        if (isset($data['discount_promos'])) {
            $cek = $this->cekValidation($data['discount_promos']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        if (isset($data['discount_stores'])) {
            $cek = $this->cekValidation($data['discount_stores']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        if (isset($data['discount_expired'])) {
            $cek = $this->cekValidation($data['discount_expired']);
            if ($cek) {
                return $this->response('Ada data diskon yang duplikat. Silahkan periksa kembali data input Anda.', 'error', 422);
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $id = Auth::id();
            $model = Branch::find($data['branch_id']);
            $model->update([
                'discount_active' => $data['discount_active']
            ]);

            $this->model->where('branch_id', $data['branch_id'])->delete();
            if (isset($data['discount_promos'])) {
                foreach ($data['discount_promos'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'promo';
                    $this->model->create($value);
                }
            }

            if (isset($data['discount_stores'])) {
                foreach ($data['discount_stores'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'store';
                    $this->model->create($value);
                }
            }

            if (isset($data['discount_expired'])) {
                foreach ($data['discount_expired'] as $value) {
                    $value = $this->discountType($value, $data, $id);
                    $value['discount_category'] = 'expired';
                    $this->model->create($value);
                }
            }

            /**
             * Clear cache
             */
            Cache::flush();

            return $model;
        });

        return $this->response(true);
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
            return $this->model->whereIn('branch_id', $data['id'])->delete();
        });

        /**
         * Clear cache
         */
        Cache::flush();

        return $this->response($data ? true : false);
    }
}
