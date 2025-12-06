<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;
    protected $classification = [
        [
            'title' => 'Dashboard',
            'classification' => 'home',
        ],
        [
            'title' => 'Management',
            'classification' => 'management',
        ],
        [
            'title' => 'Inventory',
            'classification' => 'inventory',
        ],
        [
            'title' => 'POS',
            'classification' => 'pos',
        ],
        [
            'title' => 'Distribution',
            'classification' => 'distribution',
        ],
        [
            'title' => 'Production',
            'classification' => 'production',
        ],
        [
            'title' => 'Purchasing',
            'classification' => 'purchasing',
        ],
        [
            'title' => 'Central Report',
            'classification' => 'central_report',
        ],
    ];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(Role $model)
    {
        $this->middleware('permission:role.lihat|role.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:role.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:role.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:role.hapus', [
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
    public function listMenu(Request $request)
    {
        $classification = $this->classification;

        $data = [];
        foreach ($classification as $key => $value) {
            $menu = Menu::select('id', 'title')->with('permissions')->active()->where('classification', $value['classification'])->where('type', 'item')->search($request)->get();
            if ($value['classification'] == 'purchasing') {
                $menu->prepend(Menu::select('id', 'title')->with('permissions')->where('title', 'Approval Konversi Forecast')->first());
            }
            $data[] = [
                'title' => $value['title'],
                'role' => $menu
            ];
        }

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
            'name' => 'required|string|unique:roles,name',
            'permission_id' => 'required|array',
            'permission_id.*' => 'required|exists:permissions,id',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            foreach ($data['permission_id'] as $value) {
                $model->permissions()->create(['permission_id' => $value]);
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
    public function show(Request $request, $id)
    {
        $model = $this->model->findOrFail($id);
        $classification = $this->classification;

        $menus = $this->model->with('permissions')->findOrFail($id);

        $currentPermissions = [];
        foreach ($menus->permissions as $value) {
            $currentPermissions[] = $value->permission_id;
        }


        $data = [];
        foreach ($classification as $values) {
            $menu = Menu::select('id', 'title')->with('permissions')->active()->where('classification', $values['classification'])->where('type', 'item')->search($request)->get();
            if ($values['classification'] == 'purchasing') {
                $menu->prepend(Menu::select('id', 'title')->with('permissions')->where('title', 'Approval Konversi Forecast')->first());
            }
            $dataMenu = [];
            foreach ($menu as $value) {
                $permissions = [];
                foreach ($value->permissions as $row) {
                    $permissions[] = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'action' => $row->action,
                        'status' => in_array($row->id, $currentPermissions) ? true : false,
                    ];
                }
                $dataMenu[] = [
                    'id' => $value->id,
                    'title' => $value->title,
                    'permissions' => $permissions,
                ];
            }

            $data[] = [
                'title' => $values['title'],
                'role' => $dataMenu
            ];
        }

        $datas = [
            'name' => $model->name,
            'role' => $data
        ];

        return $this->response($datas);
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
            'name' => 'required|unique:roles,name,' . $id,
            'permission_id' => 'required|array',
            'permission_id.*' => 'required|exists:permissions,id',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->permissions()->delete();
            foreach ($data['permission_id'] as $value) {
                $model->permissions()->create(['permission_id' => $value]);
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
            'id.*' => 'required|not_in:1',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
