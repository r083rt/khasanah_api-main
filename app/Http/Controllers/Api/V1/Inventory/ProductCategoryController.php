<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
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
    public function __construct(ProductCategory $model)
    {
        $this->middleware('permission:jenis-barang.lihat|jenis-barang.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:jenis-barang.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:jenis-barang.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:jenis-barang.hapus', [
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
        ]);

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
        $model = $this->model->findOrFail($id);
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
        ]);

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
            'id.*' => 'required|not_in:1,6,14,2,4,20,15',
        ], [
            'id.*.not_in' => 'Karegori Brownis, Bolu, Cake, Roti Tawar, Roti Manis, Mentahan dan Bahan Baku tidak dapat dihapus karena digunakan untuk kondisi dibanyak fitur'
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
