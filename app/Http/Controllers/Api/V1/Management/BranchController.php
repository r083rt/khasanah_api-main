<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Management\Area;
use App\Models\Management\Territory;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BranchController extends Controller
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
    public function __construct(Branch $model)
    {
        $this->middleware('permission:branch.lihat|branch.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:branch.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:branch.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:branch.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:branch.ubah|tambah', [
            'only' => ['listArea', 'listTerritory']
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
        // $data = Cache::remember('branch', 600, function () use ($request) {
        //     return $this->model->get();
        // });

        // if ($request->search) {
        //     $data = $data->filter(function ($item) use ($request) {
        //         return false !== stripos($item, $request->search);
        //     });
        // }

        // if ($request->sort_type == 'desc') {
        //     $data = $data->sortByDesc($request->sort);
        // } else {
        //     $data = $data->sortBy($request->sort);
        // }

        // $data = collect($data)->paginate(15);

        // return $this->response($data);

        $data = $this->model->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listArea(Request $request)
    {
        $data = Area::select('id', 'name')->search($request)->where('territory_id', $request->territory_id)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listTerritory(Request $request)
    {
        $data = Territory::select('id', 'name')->search($request)->orderBy('name')->get();
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
            'code' => 'required|unique:branches,code',
            'phone' => 'required',
            'zip_code' => 'required|string',
            'material_delivery_type' => 'required|in:daily,three_days,weekly,monthly',
            'schedule' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'address' => 'nullable|string',
            'initial_capital' => 'nullable|integer',
            'note' => 'nullable|string',
            'territory_id' => 'required|exists:territories,id',
            'area_id' => 'required|exists:areas,id',
            'is_production' => 'required|boolean'
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
        $model = $this->model->with(['territory', 'area'])->findOrFail($id);
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
            'code' => 'required|unique:branches,code,' . $id,
            'phone' => 'required',
            'zip_code' => 'required|string',
            'material_delivery_type' => 'required|in:daily,three_days,weekly,monthly',
            'schedule' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'address' => 'nullable|string',
            'initial_capital' => 'nullable|integer',
            'note' => 'nullable|string',
            'territory_id' => 'required|exists:territories,id',
            'area_id' => 'required|exists:areas,id',
            'is_production' => 'required|boolean'
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
            'id.*' => 'not_in:1',
        ], [
            'id.*.not_in' => 'Cabang Kantor Pusat tidak bisa dihapus'
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
