<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductRecipeUnit;
use Illuminate\Support\Facades\DB;

class ProductRecipeUnitController extends Controller
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
    public function __construct(ProductRecipeUnit $model)
    {
        $this->middleware('permission:resep-unit.lihat|resep-unit.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:resep-unit.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:resep-unit.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:resep-unit.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:resep-unit-child.lihat', [
            'only' => ['indexChild']
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
        $data = $this->model->with(['parentId2:id,name', 'parentId3:id,name', 'parentId4:id,name', 'parentId:id,name'])->whereNull('parent_id')->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexChild(Request $request)
    {
        $data = $this->model->with(['parentId2:id,name', 'parentId3:id,name', 'parentId4:id,name', 'parentId:id,name'])->whereNotNull('parent_id')->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listParent(Request $request)
    {
        $data = $this->model->select('id', 'name')->whereNull('parent_id')->search($request)->orderBy('name')->get();
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
            'note' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_recipe_units,id',
            'parent_id_2' => 'nullable|exists:product_recipe_units,id',
            'parent_id_2_conversion' => 'required_with:parent_id_2',
            'parent_id_3' => 'nullable|exists:product_recipe_units,id',
            'parent_id_3_conversion' => 'required_with:parent_id_3',
            'parent_id_4' => 'nullable|exists:product_recipe_units,id',
            'parent_id_4_conversion' => 'required_with:parent_id_4',
        ]);

        if (!isset($data['parent_id'])) {
            $data['parent_id'] = null;
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->create($data);
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
        $model = $this->model->with(['parentId2:id,name', 'parentId3:id,name', 'parentId4:id,name', 'parentId:id,name'])->findOrFail($id);
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
            'note' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_recipe_units,id',
            'parent_id_2' => 'nullable|exists:product_recipe_units,id',
            'parent_id_2_conversion' => 'required_with:parent_id_2',
            'parent_id_3' => 'nullable|exists:product_recipe_units,id',
            'parent_id_3_conversion' => 'required_with:parent_id_3',
            'parent_id_4' => 'nullable|exists:product_recipe_units,id',
            'parent_id_4_conversion' => 'required_with:parent_id_4',
        ]);

        if (!isset($data['parent_id_2']) && !isset($data['parent_id_2_conversion'])) {
            $data['parent_id_2'] = null;
            $data['parent_id_2_conversion'] = null;
        }

        if (!isset($data['parent_id_3']) && !isset($data['parent_id_3_conversion'])) {
            $data['parent_id_3'] = null;
            $data['parent_id_3_conversion'] = null;
        }

        if (!isset($data['parent_id_4']) && !isset($data['parent_id_4_conversion'])) {
            $data['parent_id_4'] = null;
            $data['parent_id_4_conversion'] = null;
        }

        if (is_null($data['parent_id']) || $data['parent_id'] == '') {
            $data['parent_id'] = null;
        }

        if (is_null($data['parent_id'])) {
            $data['parent_id_2'] = null;
            $data['parent_id_2_conversion'] = null;
            $data['parent_id_3'] = null;
            $data['parent_id_3_conversion'] = null;
            $data['parent_id_4'] = null;
            $data['parent_id_4_conversion'] = null;
        }

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            return $model->update($data);
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

        return $this->response($data ? true : false);
    }
}
