<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoSjItem;
use App\Models\ProductIngredient;
use Illuminate\Support\Facades\DB;

class PoAdjustmentOrderIngredientController extends Controller
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
    public function __construct(PoOrderIngredient $model)
    {
        $this->middleware('permission:po-adjustment-bahan.lihat|po-adjustment-bahan.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:po-adjustment-bahan.ubah', [
            'only' => ['approval']
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
        $data = $this->model->where('status', 'product_incomplete')->with(['details', 'createdBy:id,name', 'details.ingredient:id,name,code', 'details.ingredient.unit', 'branch:id,name'])->branch()->available()->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBahan(Request $request)
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
        $model = $this->model->where('status', 'product_incomplete')->with(['details', 'createdBy:id,name', 'details.ingredient:id,name,code', 'details.ingredient.unit', 'statusLogs', 'statusLogs.createdBy:id,name', 'branch:id,name'])->branch()->available()->findOrFail($id);
        foreach ($model->details as $key => $value) {
            $poSjItem = PoSjItem::select('qty_real')->where('po_id', $id)->where('type', 'po_order_ingredient')->where('product_ingredient_id', $value->product_ingredient_id)->first();
            $value->qty_real = $poSjItem ? $poSjItem->qty_real : null;
        }

        return $this->response($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approval(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:accepted,rejected',
        ]);

        $model = $this->model->findOrFail($id);
        if ($data['status'] == 'accepted') {
            $data['status'] = 'done';
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->statusLogs()->updateOrCreate(
                [
                    'status' => $data['status']
                ],
                [
                    'status' => $data['status']
                ],
            );

            return $model;
        });

        return $this->response($data);
    }
}
