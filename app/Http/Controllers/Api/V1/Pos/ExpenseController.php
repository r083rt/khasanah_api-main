<?php

namespace App\Http\Controllers\Api\V1\Pos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Pos\Expense;
use App\Models\Pos\MasterExpense;
use App\Models\ProductIngredient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
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
    public function __construct(Expense $model)
    {
        $this->middleware('permission:biaya.lihat|biaya.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:biaya.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:biaya.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:biaya.hapus', [
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
        $data = $this->model->search($request)->branch()->byMe()->sort($request)->where('date', '>=', $request->start_date)->where('date', '<=', $request->end_date);
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
    public function listMaster(Request $request)
    {
        $data = MasterExpense::select('id', 'name')->search($request)->orderBy('name')->get();
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
            'cost' => 'required|min:1',
            'qty' => 'required|integer|min:1',
            'category' => 'required|in:ingredient,general',
            'note' => 'required|string',
            'product_ingredient_id' => 'required_if:category,ingredient|exists:product_ingredients,id',
            'master_expense_id' => 'required_if:category,general|exists:master_expenses,id',
        ]);

        if ($data['category'] == 'general') {
            $data['product_ingredient_id'] = null;
        } else {
            $data['master_expense_id'] = 6;
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
        $model = $this->model->byMe()->branch()->findOrFail($id);
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
            'date' => 'required|date',
            'cost' => 'required||min:1',
            'qty' => 'required|integer|min:1',
            'category' => 'required|in:ingredient,general',
            'note' => 'required|string',
            'product_ingredient_id' => 'required_if:category,ingredient|exists:product_ingredients,id',
            'master_expense_id' => 'required_if:category,general|exists:master_expenses,id',
        ]);

        if ($data['category'] == 'general') {
            $data['product_ingredient_id'] = null;
        } else {
            $data['master_expense_id'] = 6;
        }

        $model = $this->model->select('id')->byMe()->findOrFail($id);

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
            return $this->model->whereIn('id', $data['id'])->byMe()->delete();
        });

        return $this->response($data ? true : false);
    }
}
