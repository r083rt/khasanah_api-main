<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Production\RealGrindBrownies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RealGrindBrowniesController extends Controller
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
    public function __construct(RealGrindBrownies $model)
    {
        $this->middleware('permission:real-giling-brownies.lihat|real-giling-brownies.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-brownies.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-brownies.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-brownies.hapus', [
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
        $date = $request->date;
        $data = $this->model->select('date', 'created_by')->with(['createdBy:id,name'])->where('branch_id', Auth::user()->branch_id)->groupBy('date')->search($request)->sort($request);
        if ($date) {
            $data = $data->where('date', $date);
        }
        $data = $data->paginate($this->perPage($data));

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name')->whereIn('product_category_id', config('production.brownies_target_product_category_id'))->search($request)->orderBy('name')->get();
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
            'date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.total_grind' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            foreach ($data['products'] as $value) {
                $value['date'] = $data['date'];
                $this->model->create($value);
            }

            return true;
        });

        return $this->response($data ? true : false);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->where('date', $id)->where('branch_id', Auth::user()->branch_id)->get();
        return $this->response($model);
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
            'date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.total_grind' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $this->model->where('date', $data['date'])->where('branch_id', Auth::user()->branch_id)->delete();
            foreach ($data['products'] as $value) {
                $value['date'] = $data['date'];
                $this->model->where('product_id', $value['product_id'])->where('date', $value['date'])->create($value);
            }

            return true;
        });

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
            'date' => 'required|array',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('date', $data['date'])->where('branch_id', Auth::user()->branch_id)->delete();
        });

        return $this->response($data ? true : false);
    }
}
