<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Reporting\ReportRecipe;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Management\Division;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
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
    public function __construct(ProductRecipe $model)
    {
        $this->middleware('permission:resep.lihat|resep.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:resep.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:resep.hapus', [
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
        $data = Product::select('id', 'name', 'code')->orderBy('name')->with(['recipes.ingredient', 'recipes.user'])->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listIngredient(Request $request)
    {
        $data = ProductIngredient::select('id', 'name', 'code')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listUnit(Request $request)
    {
        $product_ingredient_id = $request->product_ingredient_id;
        $product_recipe_unit_ids = DB::connection('mysql')->table('product_ingredient_units')->where('product_ingredient_id', $product_ingredient_id)->pluck('product_recipe_unit_id');
        $data = ProductRecipeUnit::select('id', 'name')->whereIn('id', $product_recipe_unit_ids)->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listDivision(Request $request)
    {
        $data = Division::select('id', 'name')->whereNotNull('parent_id')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listPackaing(Request $request)
    {
        $data = Packaging::select('id', 'name')->search($request)->orderBy('name')->get();
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
            'product_id' => 'required|exists:products,id',
            'master_packaging_id' => 'nullable|exists:master_packagings,id',
            'product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'measure' => 'nullable',
            'division_id' => 'nullable|exists:divisions,id',
        ]);
        $data['created_by'] = Auth::id();
        $productId = $data['product_id'];

        if (isset($data['product_ingredient_id'])) {
            $productIngredient = ProductIngredient::find($data['product_ingredient_id']);
            $data['product_recipe_unit_id'] = $productIngredient->product_recipe_unit_id;
            $cek = ProductRecipe::where([
                'product_id' => $data['product_id'],
                'product_ingredient_id' => $data['product_ingredient_id'],
            ])->first();
            if ($cek) {
                return $this->response('Resep dengan bahan tersebut sudah ada.', 'error', 422);
            }
        } else {
            $cek = ProductRecipe::where([
                'product_id' => $data['product_id'],
                'master_packaging_id' => $data['master_packaging_id'],
            ])->first();
            if ($cek) {
                return $this->response('Resep dengan packaging tersebut sudah ada.', 'error', 422);
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);

            if (isset($data['product_ingredient_id'])) {
                if ($recipe = ProductIngredient::select('id', 'hpp')->where('id', $data['product_ingredient_id'])->first()) {
                    if ($product = Product::select('id', 'price_sale')->where('id', $data['product_id'])->first()) {
                        $product->update([
                            'price_sale' => $product->price_sale + $recipe->hpp
                        ]);
                    }
                }
            }

            return $model;
        });

        if ($data) {
            dispatch(new ReportRecipe([
                'product_id' => $productId
            ]));
        }

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
        $data = Product::select('id', 'name', 'code')->with(['recipes.ingredient', 'recipes.ingredient.brand', 'recipes.user', 'recipes.unit', 'recipes.division', 'recipes.masterPackaging:id,name'])->findOrFail($id);
        return $this->response($data);
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
            'recipes.*' => 'required|array',
            'recipes.*.id' => 'required',
            'recipes.*.measure' => 'nullable',
            'recipes.*.division_id' => 'nullable|exists:divisions,id',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $productId = null;
            foreach ($data['recipes'] as $value) {
                if (is_null($value['division_id']) || $value['division_id'] == 'null' ||  $value['division_id'] == '') {
                    $value['division_id'] = null;
                }

                $model = $this->model->where('id', $value['id'])->first();
                if ($model) {
                    $model->update($value);
                }

                $productId = $model->product_id;
            }

            return $productId;
        });

        if ($data) {
            dispatch(new ReportRecipe([
                'product_id' => $data
            ]));
        }

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
            $model = $this->model->whereIn('id', $data['id'])->first();
            if ($model) {
                dispatch(new ReportRecipe([
                    'product_id' => $model->product_id
                ]));
            }
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }

    public function all(Request $request)
    {
        $data = Product::select('id', 'name', 'code')->orderBy('name')->with(['recipes.ingredient', 'recipes.user'])->get();
        return $this->response($data);
    }
}
