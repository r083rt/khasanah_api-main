<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Pos\MasterExpense;
use App\Models\ProductIngredient;
use Illuminate\Support\Facades\DB;

class MasterExpenseController extends Controller
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
    public function __construct(MasterExpense $model)
    {
        $this->middleware('permission:master-biaya.lihat|master-biaya.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:master-biaya.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:master-biaya.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:master-biaya.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:master-biaya.tambah|bahan-resep.ubah', [
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
            'nomor' => 'required|string',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
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
            'nomor' => 'required|string',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
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
