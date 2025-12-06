<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Production\CookieBufferProduction;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\DB;

class CookieBufferProductionController extends Controller
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
    public function __construct(CookieBufferProduction $model, BranchService $branchService, ProductService $productService)
    {
        $this->middleware('permission:roti-manis-buffer.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:roti-manis-buffer.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:roti-manis-buffer.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:roti-manis-buffer.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:roti-manis-buffer.lihat|roti-manis-buffer.show|permission:roti-manis-buffer.tambah|permission:roti-manis-buffer.ubah', [
            'only' => ['listBranch']
        ]);
        $this->middleware('permission:roti-manis-buffer.lihat', [
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
            $model = false;
            if ($data['branch_id'] == 0) {
                $branches = Branch::select('id')->get();
                foreach ($branches as $row) {
                    foreach ($data['products'] as $value) {
                        //filter product id dengan produksi harian
                        //ini bisa dilakukan setelah ada produksi harian
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
            } else {
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
                            'buffer' => $value['buffer'],
                        ]
                    );
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
            $branchId = $row->id ?? null;
            $products = $this->productService->getAllCookie(false, $branchId, $day);
            foreach ($products as $value) {
                $cek = CookieBufferProduction::select('buffer')
                    ->where('product_id', $value->id)
                    ->where('branch_id', $branchId)
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
