<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Imports\ProductIngredientImport;
use App\Models\Inventory\Brand;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Management\Division;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use App\Models\Purchasing\PurchasingSupplier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductIngredientController extends Controller
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
    public function __construct(ProductIngredient $model)
    {
        $this->middleware('permission:bahan-resep.lihat|bahan-resep.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:bahan-resep.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:bahan-resep.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:bahan-resep.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:bahan-resep.tambah|bahan-resep.ubah', [
            'only' => ['listUnit']
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
        $data = $this->model->with(['unit'])->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll(Request $request)
    {
        $data = $this->model->with(['unit'])->get();
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
            'brand_id' => 'nullable|exists:brands,id',
            // 'code' => 'required|string|unique:product_ingredients,code',
            'hpp' => 'nullable|integer',
            'product_ingredient_unit_delivery_id' => 'required|exists:product_recipe_units,id',
            // 'unit_value' => 'required|integer',
            'product_recipe_unit_id' => 'required|exists:product_recipe_units,id',
            'product_brands' => 'nullable|array',
            // 'product_brands.*.brand_id' => 'required|exists:brands,id',
            'product_brands.*.product_recipe_unit_id' => 'required|exists:product_recipe_units,id',
            'product_brands.*.barcode' => 'required|unique:product_ingredient_brands,barcode',
            'suppliers' => 'nullable|array',
            'suppliers.*.purchasing_supplier_id' => 'required|exists:purchasing_suppliers,id',
        ]);
        // $data['barcode'] = $data['code'];

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            if (isset($data['product_brands']) && !empty($data['product_brands'])) {
                foreach ($data['product_brands'] as $value) {
                    $model->productIngredientBrands()->create($value);
                }
            }
            if (isset($data['suppliers']) && $data['suppliers']) {
                $model->suppliers()->attach($data['suppliers']);
            }
            return $model;
        });

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listUnit(Request $request)
    {
        $isParent = $request->is_parent;
        $ids = $request->ids;
        $data = ProductRecipeUnit::select('id', 'name', 'parent_id_2', 'parent_id_3', 'parent_id_4')
            ->with(['parentId2:id,name', 'parentId3:id,name', 'parentId4:id,name']);

        if ($isParent) {
            $explode = explode(',', $ids);
            $data = $data->whereNull('parent_id')->whereIn('id', $explode);
        } else {
            $data = $data->whereNotNull('parent_id');
        }

        $data = $data->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBrand(Request $request)
    {
        $data = Brand::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSupplier(Request $request)
    {
        $data = PurchasingSupplier::select('id', 'name')->search($request)->orderBy('name')->get();
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
        $model = $this->model->with(['division', 'unit', 'unit.parentId2', 'unit.parentId3', 'unit.parentId4', 'unitDelivery:id,name','productIngredientBrands', 'productIngredientBrands.brand', 'productIngredientBrands.productRecipeUnit', 'suppliers:id,name', 'brand:id,name'])->findOrFail($id);
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
            'brand_id' => 'nullable|exists:brands,id',
            // 'code' => 'required|string|unique:product_ingredients,code,' . $id,
            'hpp' => 'nullable|integer',
            'product_ingredient_unit_delivery_id' => 'required|exists:product_recipe_units,id',
            // 'unit_value' => 'required|integer',
            'product_recipe_unit_id' => 'required|exists:product_recipe_units,id',
            'product_brands' => 'nullable|array',
            // 'product_brands.*.brand_id' => 'required|exists:brands,id',
            'product_brands.*.product_recipe_unit_id' => 'required|exists:product_recipe_units,id',
            'product_brands.*.barcode' => 'required|string',
            'suppliers' => 'nullable|array',
            'suppliers.*.purchasing_supplier_id' => 'required|exists:purchasing_suppliers,id',
            'price' => 'nullable|integer',
            'real_price' => 'nullable|numeric'
        ]);
        // $data['barcode'] = $data['code'];

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $oldHpp = $model->hpp;
            $model->update($data);

            ProductRecipe::where('product_ingredient_id', $model->id)->update([
                'product_recipe_unit_id' => $data['product_recipe_unit_id']
            ]);

            if (isset($data['suppliers']) && $data['suppliers']) {
                $model->suppliers()->sync($data['suppliers']);
            }

            $recipes = ProductRecipe::select('id', 'product_id')->where('product_ingredient_id', $model->id)->pluck('product_id')->unique()->values();
            foreach ($recipes as $value) {
                if ($product = Product::select('id', 'price_sale')->where('id', $value)->first()) {
                    $product->update([
                        'price_sale' => ((int)$product->price_sale - (int)$oldHpp) + (int)$data['hpp']
                    ]);
                }
            }

            if (isset($data['product_brands']) && !empty($data['product_brands'])) {
                $model->productIngredientBrands()->delete();
                foreach ($data['product_brands'] as $value) {
                    $model->productIngredientBrands()->create($value);
                }
            } else {
                $model->productIngredientBrands()->delete();
            }

            return $model;
        });

        $keys = Cache::getRedis()->keys('*po-*');
        foreach ($keys as $value) {
            $key = explode(':', $value);
            Cache::forget($key[1]);
        }

        return $this->response($model);
    }

    public function searchByBarcode($id)
    {
     
        $barcode = $id;
        $data = ProductIngredient::with(['unit'])->where('barcode', $barcode)->first();

        if (!$data) {
            return $this->response(['message' => 'Product not found'], 404);
        }

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
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }

    /**
     * Import
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $data = $this->validate($request, [
            'file' => 'required|mimes:xlsx,xls|max:10000',
        ]);

        try {
            Excel::import(new ProductIngredientImport(), $request->file);

            return $this->response('Berhasil Import Data');
        } catch (\Throwable $th) {
            return $this->response('Terjadi kesalahan. Silahkan import kembali dan pastikan file sesuai format', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePatokan(Request $request)
    {
        $data = $this->validate($request, [
            'product' => 'nullable|array',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {

            foreach ($data['product'] as $item) {
                $model = $this->model->findOrFail($item['id']);
                $model->price = $item['price'];
                $model->update();
            }

            return true;
        });

        return $this->response($data);
    }


    public function all(Request $request)
    {
        $data = $this->model->with(['unit'])->get();
        return $this->response($data);
    }
}
