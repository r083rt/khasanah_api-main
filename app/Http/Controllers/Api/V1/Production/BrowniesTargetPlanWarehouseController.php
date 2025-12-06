<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\IngredientUsage;
use App\Models\Branch;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanWarehouse;
use App\Models\Production\BrowniesTargetPlanWarehouseAdditionalPo;
use App\Models\Reporting\IngredientUsage as ReportingIngredientUsage;
use App\Models\Reporting\IngredientUsageStatus;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use App\Services\Production\BrowniesTargetPlanWarehouseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrowniesTargetPlanWarehouseController extends Controller
{
    /**
     * The user repository instance.
     */

    protected $model;

    protected $branchService;

    protected $productService;

    protected $browniesTargetPlanWarehouseService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(BrowniesTargetPlanWarehouse $model, BranchService $branchService, BrowniesTargetPlanWarehouseService $browniesTargetPlanWarehouseService)
    {
        $this->middleware('permission:produksi-brownies-gudang.lihat', [
            'only' => ['index']
        ]);
        $this->middleware('permission:produksi-brownies-gudang.lihat', [
            'only' => ['listBranch']
        ]);
        $this->middleware('permission:produksi-brownies-gudang.tambah', [
            'only' => ['store']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
        $this->browniesTargetPlanWarehouseService = $browniesTargetPlanWarehouseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $productId = $request->product_id;
        $branchId = $request->branch_id;
        $date = $request->date;
        if ($branchId == '') {
            $branchId = Auth::user()->branch_id;
        }

        if ($branchId == 0 && $productId) {
            $branches = Branch::select('id', 'name')->orderBy('name')->get();
            $datas = [];
            foreach ($branches as $value) {
                $dataAll = $this->model->where('date', $date)->where('branch_id', $value->id)->where('product_id', $productId)->get();
                if (count($dataAll) > 0) {
                    $datas[] = [
                        'is_editable' => false,
                        'branch_id' => $value->id,
                        'branch_name' => $value->name,
                        'nomor_po' => $dataAll->first()->nomor_po,
                        'barcode_po' => $dataAll->first()->barcode_po,
                        'datas' => $dataAll
                    ];
                } else {
                    $dataAll = $this->browniesTargetPlanWarehouseService->getWarehouse($date, $value->id, $productId);
                    if ($dataAll->count() > 0) {
                        $datas[] = [
                            'is_editable' => true,
                            'branch_id' => $value->id,
                            'branch_name' => $value->name,
                            'nomor_po' => $dataAll->first()->nomor_po,
                            'barcode_po' => $dataAll->first()->barcode_po,
                            'datas' => $dataAll
                        ];
                    }
                }
            }
        } else {
            $editable = false;
            $datas = $this->model->where('date', $date)->where('branch_id', $branchId)->get();
            if (count($datas) == 0) {
                $editable = true;
                $datas = $this->browniesTargetPlanWarehouseService->getWarehouse($date, $branchId);
                foreach ($datas as $value) {
                    $cek = BrowniesTargetPlanWarehouseAdditionalPo::select('po')->where([
                        'branch_id' => $branchId,
                        'date' => $date,
                        'product_id' => $value->id,
                    ])->first();
                    if ($cek) {
                        $value->po = $cek->po;
                        $value->total = $cek->po;
                    }
                }
            }
            $branch = Branch::select('id', 'name')->where('id', $branchId)->first();

            if (count($datas->toArray()) > 0) {
                return $this->response([
                    [
                        'is_editable' => $editable,
                        'branch_id' => $branchId,
                        'branch_name' => $branch ? $branch->name : null,
                        'nomor_po' => $datas->first()->nomor_po,
                        'barcode_po' => $datas->first()->barcode_po,
                        'datas' => $datas
                    ]
                ]);
            } else {
                return $this->response([]);
            }
        }

        return $this->response($datas);
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
            'date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.product_code' => 'required',
            'products.*.product_name' => 'required',
            'products.*.estimation_product' => 'required|integer',
            'products.*.minimum_stock' => 'required|integer',
            'products.*.order' => 'required|integer',
            'products.*.po' => 'required|integer',
            'products.*.percentage' => 'required|integer',
            'products.*.total' => 'required|integer',
        ]);

        $cek = $this->model->where('branch_id', $data['branch_id'])->where('date', $data['date'])->count();
        if ($cek > 0) {
            return $this->response('Data sudah pernah disubmit', 'error', 422);
        }

        $products = array_filter($data['products'], function ($value) {
            return $value['total'] > 0;
        });

        $date = $data['date'];
        $branchId = $data['branch_id'];

        $data = DB::connection('mysql')->transaction(function () use ($data, $products) {
            $model = PoOrderProduct::create([
                'branch_id' => $data['branch_id'],
                'type' => 'brownies',
                'available_at' => $data['date'],
            ]);

            foreach ($data['products'] as $value) {
                $value['branch_id'] = $data['branch_id'];
                $value['date'] = $data['date'];
                $value['nomor_po'] = $model->nomor_po;
                $value['po_order_product_id'] = $model->id;
                $this->model->create($value);
            }

            $model->statusLogs()->create(['status' => 'new']);
            foreach ($products as $value) {
                $value['qty'] = $value['total'];
                $model->details()->create($value);
            }

            return $model;
        });

        IngredientUsageStatus::updateOrCreate(
            [
                'date' => $date,
                'branch_id' => $branchId,
            ],
            [
                'date' => $date,
                'branch_id' => $branchId,
                'status_po_production_brownies' => 'new',
            ]
        );
        dispatch(new IngredientUsage($date, $branchId, 'brownies'));

        return $this->response($data ? true : false);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'code', 'name')->whereIn('product_category_id', config('production.brownies_target_product_category_id'))->orderBy('name')->get();
        return $this->response($data);
    }
}
