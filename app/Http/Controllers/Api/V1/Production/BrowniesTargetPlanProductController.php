<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanProduct;
use App\Models\Production\BrowniesTargetPlanProductDetail;
use App\Models\ProductStock;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BrowniesTargetPlanProductController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(BrowniesTargetPlanProduct $model, BranchService $branchService)
    {
        $this->middleware('permission:produksi-brownies-harian.lihat|produksi-brownies-harian.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-harian.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-harian.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-harian.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
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
                'value' => 'all',
                'name' => 'Senin - Minggu',
            ],
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
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name')->whereIn('product_category_id', config('production.brownies_target_product_category_id'))->available()->search($request)->orderBy('name')->get();
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
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday,all',
            'branch_id' => 'nullable|exists:branches,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.is_production' => 'required|in:0,1',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = false;
            if ($data['day'] == 'all') {
                $day = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                foreach ($day as $row) {
                    foreach ($data['products'] as $value) {
                        $model = $this->model->updateOrCreate(
                            [
                                'branch_id' => $data['branch_id'],
                                'day' => $row,
                                'product_id' => $value['product_id'],
                            ],
                            [
                                'branch_id' => $data['branch_id'],
                                'day' => $row,
                                'product_id' => $value['product_id'],
                                'is_production' => $value['is_production'],
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
                            'is_production' => $value['is_production'],
                        ]
                    );
                }
            }

            return $model;
        });

        Artisan::call('production:brownies-target-sale');

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
            $products = $this->getListProduct();
            foreach ($products as $value) {
                $cek = BrowniesTargetPlanProduct::select('is_production')
                    ->where('product_id', $value->id)
                    ->where('branch_id', $row->id)
                    ->where('day', $day)
                    ->first();

                $is_production = false;
                if ($cek) {
                    $is_production = $cek->is_production;
                }

                $productsData[] = [
                    'id' => $value->id,
                    'is_production' => $is_production
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
     * Get List Product
     */
    private function getListProduct()
    {
        $key = 'product-brownies';
        if (!Cache::has($key)) {
            $data = Product::select('id', 'name')->whereIn('product_category_id', config('production.brownies_target_product_category_id'))->orderBy('name')->get();
            Cache::put($key, $data, 2400);
        } else {
            $data = Cache::get($key);
        }

        return $data;
    }
}
