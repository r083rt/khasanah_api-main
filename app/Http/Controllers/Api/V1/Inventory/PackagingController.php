<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Reporting\ReportRecipe;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\PackagingRecipe;
use App\Models\Management\Division;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Support\Facades\DB;

class PackagingController extends Controller
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
    public function __construct(Packaging $model)
    {
        $this->middleware('permission:master-paketan.lihat|master-paketan.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:master-paketan.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:master-paketan.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:master-paketan.hapus', [
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
        $data = $this->model->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'code', 'name', 'price')->whereIn('product_category_id', [1, 4, 6, 14, 15, 23])->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listDivision(Request $request)
    {
        $data = Division::select('id', 'name')->whereNotNull('parent_id')->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProductIngredient(Request $request)
    {
        $data = ProductIngredient::select('id', 'name')->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listPackaging(Request $request)
    {
        $data = Packaging::select('id', 'name')->orderBy('name')->get();
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
            'type' => 'required|in:brownies,sponge,cake,cookie,bread,cream',
            'grinds' => 'required',
            'gramasi' => 'nullable',
            'gramasi_production' => 'nullable',
            'unit' => 'required|in:Gram',
            'barcode' => 'required',
            'code' => 'nullable',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|integer',
            'recipes' => 'nullable|array',
            'recipes.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'recipes.*.master_packaging_recipe_id' => 'nullable|exists:master_packagings,id',
            'recipes.*.measure' => 'required',
            'recipes.*.division_id' => 'nullable|exists:divisions,id',
        ]);

        //validasi packaging

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            dispatch(new ReportRecipe([
                'master_packaging_id' => $model->id
            ]));
            $model->products()->attach($data['products']);
            foreach ($data['recipes'] as $value) {
                $model->recipes()->create($value);
            }
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
        $model = $this->model->with([
            'products:id,name',
            'recipes',
            'recipes.productIngredient:id,name,product_recipe_unit_id',
            'recipes.productIngredient.unit:id,name',
            'recipes.division:id,name',
            'recipes.packagingRecipe:id,name',
        ])->findOrFail($id);

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
            'type' => 'required|in:brownies,sponge,cake,cookie,bread,cream',
            'grinds' => 'required',
            'gramasi' => 'nullable',
            'gramasi_production' => 'nullable',
            'unit' => 'required|in:Gram',
            'barcode' => 'required',
            'code' => 'nullable',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|integer',
            'recipes' => 'nullable|array',
            'recipes.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'recipes.*.master_packaging_recipe_id' => 'nullable|exists:master_packagings,id',
            'recipes.*.measure' => 'required',
            'recipes.*.division_id' => 'nullable|exists:divisions,id',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->products()->sync($data['products']);
            $model->recipes()->delete();
            foreach ($data['recipes'] as $value) {
                $model->recipes()->create($value);
            }
            return $model;
        });

        dispatch(new ReportRecipe([
            'master_packaging_id' => $id
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
            'id.*' => 'required',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->whereIn('id', $data['id'])->get();
            foreach ($model as $value) {
                dispatch(new ReportRecipe([
                    'master_packaging_id' => $value->id
                ]));
            }

            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $id)
    {
        $data = DB::connection('mysql')->transaction(function () use ($id) {
            return PackagingRecipe::where('id', $id)->delete();
        });

        return $this->response($data ? true : false);
    }

    public function all(Request $request)
    {
        $data = $this->model->get();
        return $this->response($data);
    }
}
