<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
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
    public function __construct(User $model)
    {
        $this->middleware('permission:user.lihat|user.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:user.tambah', [
            'only' => ['store', 'listRole', 'listBranch']
        ]);
        $this->middleware('permission:user.ubah', [
            'only' => ['update', 'listRole', 'listBranch']
        ]);
        $this->middleware('permission:user.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model->with(['branch'])->branch()->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listRole(Request $request)
    {
        $data = Role::select('id', 'name')->search($request)->orderBy('name')->get();
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
        $data = Branch::select('id', 'name')->branch()->search($request)->orderBy('name')->get();
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
            'branch_id' => 'nullable|exists:branches,id',
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'phone' => 'nullable',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'password' => 'required|min:8|confirmed',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'active';

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
        $model = $this->model->select('*')->branch()->findOrFail($id);
        return $this->response($model);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validate($request, [
            'branch_id' => 'nullable|exists:branches,id',
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id . ',id,deleted_at,NULL',
            'phone' => 'nullable',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $model = $this->model->select('*')->branch()->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            if (isset($data['password']) && $data['password'] != '') {
                $data['password'] = Hash::make($data['password']);
                return $model->update($data);
            } else {
                return $model->update(Arr::except($data, ['password']));
            }
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
            return $this->model->select('*')->branch()->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
