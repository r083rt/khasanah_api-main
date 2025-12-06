<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Menu;
use DB;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
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
    public function __construct(Menu $model)
    {
        $this->middleware('permission:menu.lihat|menu.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:menu.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:menu.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:menu.hapus', [
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
        $data = $this->model->with('permissions')->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allAccess()
    {
        $menuIds = Menu::getAvailableMenuId(Auth::user()->role_id);

        $menu = Menu::where(function ($query) {
            $query->whereNull('parent_id')
                  ->orWhere('type', 'collapse');
        })
        ->where('is_displayed', 1)
        ->orderBy('order_menu')
        ->get();

        $menus = [];
        foreach ($menu as $key => $value) {
            if ($value->title == 'PO Pesanan') {
                $product = PoOrderProduct::where('status', 'new')->available()->branch()->count();
                $ingredient  = PoOrderIngredient::where('status', 'new')->available()->branch()->count();
                $value->badge = [
                    'title' => $product + $ingredient,
                    'bg' => '#F44336',
                    'fg' => '#FFFFFF'
                ];
            }

            if ($value->type == 'item') {
                if (in_array($value->id, $menuIds)) {
                    $value->children = [];
                    $menus[] = $value->toArray();
                }
            } else {
                $children = Menu::getChild($value->id, $menuIds);
                foreach ($children as $row) {
                    if ($row->title == 'Po Pesanan Product') {
                        $product = PoOrderProduct::where('status', 'new')->available()->branch()->count();
                        $row->badge = [
                            'title' => $product,
                            'bg' => '#F44336',
                            'fg' => '#FFFFFF'
                        ];
                    }

                    if ($row->title == 'Po Pesanan Bahan') {
                        $ingredient  = PoOrderIngredient::where('status', 'new')->available()->branch()->count();
                        $row->badge = [
                            'title' => $ingredient,
                            'bg' => '#F44336',
                            'fg' => '#FFFFFF'
                        ];
                    }
                }

                $value->children = $children;
                $menus[] = $value->toArray();
            }
        }

        return $this->response($menus);
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
            'title' => 'required|string|unique:menus,title',
            'parent_id' => 'nullable|integer|unique:menus,id',
            'classification' => 'required',
            'icon' => 'required',
            'type' => 'required|in:collapse,item',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.action' => 'required|string',
            'order_menu' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model =  $this->model->create($data);
            foreach ($data['permissions'] as $key => $value) {
                $model->permissions()->create($value);
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
        $model = $this->model->with('permissions')->findOrFail($id);
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
            'title' => 'required|string|unique:menus,title,' . $id,
            'parent_id' => 'nullable|integer|unique:menus,id,'  . $id,
            'classification' => 'required',
            'icon' => 'required',
            'type' => 'required|in:collapse,item',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.action' => 'required|string',
            'order_menu' => 'required|integer',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->permissions()->delete();
            foreach ($data['permissions'] as $key => $value) {
                $model->permissions()->create($value);
            }

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

        return $this->response($data ? true : false);
    }
}
