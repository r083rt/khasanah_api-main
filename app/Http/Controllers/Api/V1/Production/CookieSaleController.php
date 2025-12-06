<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Production\BrowniesTargetPlanSale;
use App\Models\Production\CookieSale;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\DB;

class CookieSaleController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    protected $productService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(CookieSale $model, BranchService $branchService, ProductService $productService)
    {
        $this->middleware('permission:roti-manis-penjualan.lihat|roti-manis-penjualan.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-penjualan.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-penjualan.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-penjualan.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->response($this->day());
    }

    /**
     * Get day
     */
    private function day()
    {
        return [
            [
                'value' => 'monday',
                'name' => 'Senin',
            ],
            [
                'value' => 'tuesday',
                'name' => 'Selasa',
            ],
            [
                'value' => 'wednesday',
                'name' => 'Rabu',
            ],
            [
                'value' => 'thursday',
                'name' => 'Kamis',
            ],
            [
                'value' => 'friday',
                'name' => 'Jumat',
            ],
            [
                'value' => 'saturday',
                'name' => 'Sabtu',
            ],
            [
                'value' => 'sunday',
                'name' => 'Minggu',
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        return $this->response($this->branchService->getAll());
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
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'branch_id' => 'nullable|exists:branches,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.target' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = false;
            foreach ($data['products'] as $value) {
                $model = $this->model->updateOrCreate(
                    [
                        'branch_id' => $data['branch_id'],
                        'day' => $data['day'],
                        'product_id' => $value['product_id'],
                    ],
                    [
                        'branch_id' => $data['branch_id'],
                        'day' => $data['day'],
                        'product_id' => $value['product_id'],
                        'target' => $value['target'],
                    ]
                );
            }

            return $model;
        });

        return $this->response($data ? true : false);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $day)
    {
        $data = collect($this->day())->where('value', $day)->values()->first();

        $branchData = [];
        if ($branchId = $request->branch_id) {
            $filter = [
                'id' => $branchId
            ];
            $branches = $this->branchService->getAll(null, $filter);
        } else {
            $branches = [];
        }
        foreach ($branches as $row) {
            $productsData = [];
            $products = $this->productService->getAllCookie(false, $row->id, $day);
            foreach ($products as $value) {
                $cek = $this->model->select('target')
                    ->where('product_id', $value->id)
                    ->where('branch_id', $row->id)
                    ->where('day', $day)
                    ->first();

                $target = 0;
                if ($cek) {
                    $target = $cek->target;
                }

                $productsData[] = [
                    'id' => $value->id,
                    'target' => $target,
                    'name' => $value->name,
                ];
            }

            $branchData[] = [
                'branch_id' => $row->id,
                'branch_name' => $row->name,
                'products' => $productsData
            ];
        }

        $data['datas'] = $branchData;

        return $this->response($data);
    }
}
