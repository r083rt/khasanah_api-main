<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastBuffer;
use Illuminate\Support\Facades\DB;

class ForecastBufferController extends Controller
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
    public function __construct(ForecastBuffer $model)
    {
        $this->middleware('permission:forecast-buffer.lihat|forecast-buffer.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:forecast-buffer.ubah', [
            'only' => ['update']
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
        $data = ProductIngredient::select('id', 'name', 'code')->orderBy('name')->with(['buffer'])->search($request)->sort($request);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->where('product_ingredient_id', $id)->with(['productIngredient:id,name'])->first();
        if(!$model){
            $model = new ForecastBuffer();
            $model->product_ingredient_id = $id;
            $model->buffer = 0;
            $model->save();
            $model = $this->model->where('product_ingredient_id', $id)->with(['productIngredient:id,name'])->first();
        }
        return $this->response($model);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $month
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'buffer' => 'required|integer',
            'product_ingredient_id' => 'required|exists:product_ingredients,id',
        ]);

        $model = $this->model->where('product_ingredient_id', $data['product_ingredient_id'])->first();

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            if ($model) {
                return $model->update($data);
            } else {
                return $this->model->create($data);
            }
        });

        return $this->response($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $month
     * @return \Illuminate\Http\Response
     */
    public function default(Request $request)
    {
        $data = $this->validate($request, [
            'buffer' => 'required|integer',
        ]);

        $model = $this->model->get();

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            foreach ($model as $value) {
                $value->update($data);
            }

            return true;
        });

        return $this->response($data);
    }
}
