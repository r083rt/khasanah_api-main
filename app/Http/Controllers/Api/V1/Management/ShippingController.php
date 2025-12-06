<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Management\Shipping;
use App\Models\Management\ShippingTracks;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
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
    public function __construct(Shipping $model)
    {
        $this->middleware('permission:pengiriman.lihat|pengiriman.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:pengiriman.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:pengiriman.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:pengiriman.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:pengiriman.tambah|pengiriman.ubah', [
            'only' => ['listBranch']
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
        $data = $this->model->with(['tracks'])->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $shippingId = $request->shipping_id;
        if ($shippingId) {
            $branchIds = ShippingTracks::where('shipping_id', '!=', $shippingId)->pluck('branch_id');
        } else {
            $branchIds = ShippingTracks::pluck('branch_id');
        }

        $data = Branch::select('id', 'name')->whereNotIn('id', $branchIds)->search($request)->orderBy('name')->get();
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
            'branches' => 'required|array',
            'branches.*.branch_id' => 'required|exists:branches,id',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            $model->tracks()->attach($data['branches']);

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
        $model = $this->model->with(['tracks'])->findOrFail($id);
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
            'branches' => 'required|array',
            'branches.*.branch_id' => 'required|exists:branches,id',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->tracks()->sync($data['branches']);

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
            'id.*' => 'integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
