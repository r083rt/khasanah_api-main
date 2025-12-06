<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanBuffer;
use App\Models\Production\BrowniesTargetPlanProduct;
use App\Models\Production\BrowniesTargetPlanProductDetail;
use App\Models\Production\BrowniesTargetPlanSale;
use App\Models\ProductStock;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BrowniesTargetPlanBufferController extends Controller
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
    public function __construct(BrowniesTargetPlanBuffer $model, BranchService $branchService, ProductService $productService)
    {
        $this->middleware('permission:produksi-brownies-buffer.lihat|produksi-brownies-buffer.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-buffer.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-buffer.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-buffer.hapus', [
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
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'branch_id' => 'required',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.buffer' => 'required|integer',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = true;
            if ($data['branch_id'] == 0) {
                $branches = Branch::select('id')->get();
                foreach ($branches as $row) {
                    $product = BrowniesTargetPlanProduct::select('product_id')->where('is_production', 1)->where('branch_id', $row->id)->where('day', $data['day'])->pluck('product_id')->unique()->toArray();
                    foreach ($data['products'] as $value) {
                        if (in_array($value['product_id'], $product)) {
                            $model = $this->model->updateOrCreate(
                                [
                                    'branch_id' => $row->id,
                                    'day' => $data['day'],
                                    'product_id' => $value['product_id'],
                                ],
                                [
                                    'branch_id' => $row->id,
                                    'day' => $data['day'],
                                    'product_id' => $value['product_id'],
                                    'buffer' => $value['buffer'],
                                ]
                            );
                        }
                    }
                }
            } else {
                $product = BrowniesTargetPlanProduct::select('product_id')->where('is_production', 1)->where('branch_id', $data['branch_id'])->where('day', $data['day'])->pluck('product_id')->unique()->toArray();
                foreach ($data['products'] as $value) {
                    if (in_array($value['product_id'], $product)) {
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
                                'buffer' => $value['buffer'],
                            ]
                        );
                    }
                }
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
        $branchId = $request->branch_id;
        if ($branchId != 0) {
            $filter = [
                'id' => $branchId
            ];
            $branches = $this->branchService->getAll(null, $filter);
        } else {
            if (is_null($branchId)) {
                $branches = [];
            } else {
                $branches = collect([]);
                $branches->push((object)[
                    'id' => 0,
                    'name' => "Semua Cabang",
                ]);
            }
        }
        foreach ($branches as $row) {
            $productsData = [];
            $products = $this->productService->getAllBrownies(false, $row->id, $day);
            foreach ($products as $value) {
                $cek = BrowniesTargetPlanBuffer::select('buffer')
                    ->where('product_id', $value->id)
                    ->where('branch_id', $row->id)
                    ->where('day', $day)
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
}
