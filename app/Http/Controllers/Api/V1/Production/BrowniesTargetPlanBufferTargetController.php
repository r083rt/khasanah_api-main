<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Production\BrowniesTargetPlanBufferTarget;
use App\Models\Production\CookieBufferProduction;
use App\Models\Production\CookieBufferTarget;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\DB;

class BrowniesTargetPlanBufferTargetController extends Controller
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
    public function __construct(BrowniesTargetPlanBufferTarget $model, BranchService $branchService, ProductService $productService)
    {
        $this->middleware('permission:produksi-brownies-buffer-target.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:produksi-brownies-buffer-target.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:produksi-brownies-buffer-target.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:produksi-brownies-buffer-target.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:produksi-brownies-buffer-target.lihat|produksi-brownies-buffer-target.show|permission:produksi-brownies-buffer-target.tambah|permission:produksi-brownies-buffer-target.ubah', [
            'only' => ['listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-buffer-target.lihat', [
            'only' => ['show']
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
        $branchData = [];
        $branchId = $request->branch_id;
        $day = date_to_day($request->date);
        $date_day = $request->date_day;
        $month = $request->date_month;
        $year = $request->date_year;
        if ($branchId != 0) {
            $filter = [
                'id' => $branchId
            ];
            $branches = $this->branchService->getAll(null, $filter);
        } else {
            $branches = collect([]);
            $branches->push((object)[
                'id' => 0,
                'name' => "Semua Cabang",
            ]);
        }
        foreach ($branches as $row) {
            $productsData = [];
            $branchId = $row->id ?? null;
            $products = $this->productService->getAllBrownies(false, $branchId, $day);
            foreach ($products as $value) {
                $cek = $this->model->select('buffer')
                    ->where('product_id', $value->id)
                    ->where('branch_id', $branchId)
                    ->where('date_day', $date_day)
                    ->where('date_month', $month)
                    ->where('date_year', $year)
                    ->first();

                $buffer = 0;
                if ($cek) {
                    $buffer = $cek->buffer;
                }

                $productsData[] = [
                    'id' => $value->id,
                    'buffer' => $buffer,
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        return $this->response($this->branchService->getAll()->prepend([
            'id' => 0,
            'name' => "Semua Cabang",
            'material_delivery_type_indo' => null,
            'schedule_indo' => null,
        ]));
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
            'date_day' => 'required|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31',
            'date_month' => 'required|in:1,2,3,4,5,6,7,8,9,10,11,12',
            'date_year' => 'required',
            'branch_id' => 'required',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.buffer' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = false;
            if ($data['branch_id'] == 0) {
                $branches = Branch::select('id')->get();
                foreach ($branches as $row) {
                    foreach ($data['products'] as $value) {
                        $model = $this->model->updateOrCreate(
                            [
                                'branch_id' => $row->id,
                                'date_day' => $data['date_day'],
                                'date_month' => $data['date_month'],
                                'date_year' => $data['date_year'],
                                'product_id' => $value['product_id'],
                            ],
                            [
                                'branch_id' => $row->id,
                                'date_day' => $data['date_day'],
                                'date_month' => $data['date_month'],
                                'date_year' => $data['date_year'],
                                'product_id' => $value['product_id'],
                                'buffer' => $value['buffer'],
                            ]
                        );
                    }
                }
            } else {
                foreach ($data['products'] as $value) {
                    $model = $this->model->updateOrCreate(
                        [
                            'branch_id' => $data['branch_id'],
                            'date_day' => $data['date_day'],
                            'date_month' => $data['date_month'],
                            'date_year' => $data['date_year'],
                            'product_id' => $value['product_id'],
                        ],
                        [
                            'branch_id' => $data['branch_id'],
                            'date_day' => $data['date_day'],
                            'date_month' => $data['date_month'],
                            'date_year' => $data['date_year'],
                            'product_id' => $value['product_id'],
                            'buffer' => $value['buffer'],
                        ]
                    );
                }
            }

            return $model;
        });

        return $this->response($data ? true : false);
    }
}
