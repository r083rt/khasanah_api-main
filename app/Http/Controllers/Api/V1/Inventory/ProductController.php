<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use App\Jobs\Reporting\ReportRecipe;
use App\Models\Branch;
use App\Models\Inventory\ProductCode;
use App\Models\Inventory\ProductCodeNew;
use App\Models\Pos\ProductFirstStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
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
    public function __construct(Product $model, StockService $stockService)
    {
        $this->middleware('permission:barang.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:barang.show|barang.lihat', [
            'only' => ['show']
        ]);
        $this->middleware('permission:barang.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:barang.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:barang.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:barang.lihat|barang.show|barang.tambah|barang.ubah', [
            'only' => ['listUnit']
        ]);
        $this->middleware('permission:barang.lihat|barang.show|barang.tambah|barang.ubah', [
            'only' => ['listCategory']
        ]);
        $this->middleware('permission:barang.lihat|barang.show|barang.tambah|barang.ubah', [
            'only' => ['listBranch']
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
        $data = $this->model->select('id', 'name', 'price', 'code', 'barcode')->search($request)->sort($request);
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
    public function listUnit(Request $request)
    {
        $data = ProductUnit::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name', 'code')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Product Code
     *
     */
    public function productCode()
    {
        $data = ProductCodeNew::select('code')->orderByDesc('code')->first();
        if (is_null($data)) {
            $data = [
                'code' => 1,
                'code_value' => '0000001',
            ];
        }

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
            'code' => 'nullable|string',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'price' => 'required|numeric',
            'price_sale' => 'required|numeric',
            'gramasi' => 'nullable|numeric',
            'note' => 'nullable|string',
            'product_unit_delivery_id' => 'nullable|numeric',
            'unit_value' => 'nullable|numeric',
            'mill_barrel' => 'nullable|numeric',
            'shop_roller' => 'nullable|numeric',
            'stocks.*' => 'nullable|array',
            'stocks.*.branch_id' => 'required|integer',
            'stocks.*.first_stock' => 'required|integer',
        ]);
        $data['code_value'] = $data['code'];
        $data['barcode'] = $data['code'];
        $branch = Branch::select('id')->pluck('id');
        $data['branch_id'] = $branch;

        if (isset($data['code'])) {
            $cek = ProductCodeNew::where('code', $data['code'])->first();
            if ($cek) {
                return $this->response('Kode Produk sudah digunakan', 422);
            }

            $cek = Product::where('code', $data['code'])->first();
            if ($cek) {
                return $this->response('Kode Produk sudah digunakan', 422);
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            $model->availables()->attach($data['branch_id']);
            if (isset($data['code'])) {
                $data['code'] = ltrim($data['code']);
                $model->codeNew()->create($data);
            }

            if (isset($data['stocks'])) {
                foreach ($data['stocks'] as $value) {
                    $model->first_stocks()->create($value);

                    if ($productStock = ProductStock::where('product_id', $model->id)->where('branch_id', $value['branch_id'])->first()) {
                        $productStock = $productStock->udpate([
                            'stock' => $productStock + $value['first_stock']
                        ]);

                        $this->stockService->createStockLog([
                            'branch_id' => $value['branch_id'],
                            'product_id' => $model->id,
                            'stock' => $value['first_stock'],
                            'stock_old' => $productStock,
                            'from' => 'Produk',
                            'table_reference' => 'products',
                            'table_id' => $model->id
                        ]);
                    } else {
                        $value['stock'] = $value['first_stock'];
                        $model->stocks()->create($value);
                    }
                }
            }

            ProductCode::where('code', $data['code'])->delete();

            /**
             * Clear cache
             */
            Cache::flush();

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
        $branchId = Auth::user()->branch_id;
        $model = $this->model->with(['availables:id,name,code', 'first_stocks:id,branch_id,product_id,first_stock'])->findOrFail($id);
        $stock = ProductStock::select('stock')->where('product_id', $model->id)->where('branch_id', $branchId)->first();
        $model->total_stock = $stock ? $stock->stock : 0;
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
            'code' => 'required|string',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'price' => 'required|numeric',
            'price_sale' => 'required|numeric',
            'gramasi' => 'nullable|numeric',
            'note' => 'nullable|string',
            'product_unit_delivery_id' => 'nullable|numeric',
            'unit_value' => 'nullable|numeric',
            'mill_barrel' => 'nullable|numeric',
            'shop_roller' => 'nullable|numeric',
            'branch_id' => 'required|array',
            'branch_id.*' => 'required|exists:branches,id',
            'stocks.*' => 'nullable|array',
            'stocks.*.branch_id' => 'required|integer',
            'stocks.*.first_stock' => 'required|integer',
        ]);
        $data['barcode'] = $data['code'];

        $model = $this->model->available()->findOrFail($id);

        $recipes = ProductRecipe::select('id', 'product_ingredient_id')->with(['ingredient:id,hpp'])->where('product_id', $id)->get();
        // $price = 0;
        // foreach ($recipes as $key => $value) {
        //     $price = $price + $value->ingredient->hpp;
        // }
        // $data['price_sale'] = $price;

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->availables()->sync($data['branch_id']);

            if (isset($data['stocks'])) {
                foreach ($data['stocks'] as $value) {
                    $product = ProductFirstStock::select('id', 'first_stock')->where('product_id', $model->id)->where('branch_id', (int)$value['branch_id'])->first();
                    $oldStock = $product->first_stock;
                    $model->first_stocks()->where('id', $product->id)->update($value);

                    if ($productStock = ProductStock::where('product_id', $model->id)->where('branch_id', $value['branch_id'])->first()) {
                        $oldStockProduct = $productStock->stock;
                        $productStock->update([
                            'stock' => ($productStock->stock - $oldStock) + $value['first_stock']
                        ]);
                    }

                    $this->stockService->createStockLog([
                        'branch_id' => $value['branch_id'],
                        'product_id' => $model->id,
                        'stock' => $value['first_stock'],
                        'stock_old' => $oldStockProduct,
                        'from' => 'Produk',
                        'table_reference' => 'products',
                        'table_id' => $model->id
                    ]);
                }
            }

            /**
             * Clear cache
             */
            Cache::flush();

            return $model;
        });

        dispatch(new ReportRecipe([
            'product_id' => $id
        ]));

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
            foreach ($data['id'] as $key => $value) {
                $product = Product::select('id', 'code')->find($value);
                if ($product) {
                    ProductCode::create([
                        'code' => $product->code
                    ]);
                }

                dispatch(new ReportRecipe([
                    'product_id' => $value
                ]));
            }

            /**
             * Clear cache
             */
            Cache::flush();

            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }

    /**
     * Import Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function importExcel(Request $request)
    {
        $data = $this->validate($request, [
            'file' => 'required|mimes:xlsx,xls|max:10000',
        ]);

        try {
            Excel::import(new ProductsImport(), $request->file);

            /**
             * Clear cache
             */
            Cache::flush();

            return $this->response('Berhasil Import Data');
        } catch (\Throwable $th) {
            return $this->response('Terjadi kesalahan. Silahkan import kembali dan pastikan file sesuai format', 422);
        }
    }

    public function all(Request $request)
    {
        $data = $this->model->select('id', 'name', 'price', 'code', 'barcode')->get();
        return $this->response($data);
    }
}
