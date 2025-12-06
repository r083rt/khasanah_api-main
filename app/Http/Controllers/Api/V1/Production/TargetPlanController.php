<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OrderProduct;
use App\Models\Pos\Closing;
use App\Models\Product;
use App\Models\Production\TargetPlan;
use App\Models\Production\TargetPlanDetail;
use App\Models\Production\TargetPlanDetailGrind;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetPlanController extends Controller
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
    public function __construct(TargetPlan $model)
    {
        $this->middleware('permission:produksi-rencana.lihat|produksi-rencana.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:produksi-rencana.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:produksi-rencana.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:produksi-rencana.hapus', [
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
        $type = $request->type;
        $date = $request->created_at;
        $product_category_id = $request->product_category_id;
        $branchId = ($request->branch_id) ? $request->branch_id : Auth::user()->branch_id;
        $datePast = date('Y-m-d', strtotime('-1 days', strtotime($date)));

        $data = $this->model->select('id', 'branch_id', 'date')
            ->with(['branch:id,name', 'details', 'details.grinds:id,target_plan_detail_id,grind,total', 'details.product:id,name', 'details.category:id,name'])
            ->where('branch_id', $branchId);

        if ($type == 'target_plan') {
            $data = $data->where('date', $date);
        } else {
            $data = $data->where('date', $datePast);
        }

        $data = $data->first();
        if (is_null($data)) {
            $products = Product::select('id', 'id as product_id', 'name', 'code', 'product_category_id')->with(['stocks:stock,product_id', 'category:id,name'])->where('product_category_id', $product_category_id)->available(false, $branchId)->get();

            foreach ($products as $value) {
                $currentStock = $this->currentStock($branchId, $value);
                $value->current_stock = $currentStock ? $currentStock->stock : 0;

                $firstStock = $this->firstStock($branchId, $datePast, $value);
                $firstStock = $firstStock ? $firstStock->products ? $firstStock->products->first()->stock_real : 0 : 0;
                $value->first_stock = $firstStock;

                $remains = $this->firstStock($branchId, $date, $value);
                $remains = $remains ? $remains->products ? $remains->products->first()->stock_real : 0 : 0;
                $value->remains = $remains;

                $targetPlan = TargetPlanDetail::where('product_id', $value->id)->whereHas('target', function ($query) use ($branchId, $datePast) {
                    $query->where('branch_id', $branchId)->where('date', $datePast);
                })->first();

                $realization = $targetPlan ? $targetPlan->tomorrow_plan : 0;
                $value->realization = $realization;

                $two_oclock = $this->twoOclock($branchId, $value, $date, ($firstStock + $realization));
                $value->two_oclock = $two_oclock;
                $four_oclock = $this->fourOclock($branchId, $value, $date, ($firstStock + $realization));
                $value->four_oclock = $four_oclock;

                $tomorrowPlan = $this->tomorrowPlan($firstStock, $two_oclock, $four_oclock);
                $value->tomorrow_plan = $tomorrowPlan;

                $value->grinds = [];
            }
            $branch = Branch::find($branchId);

            $data = [
                'date' => $date,
                'branch_id' => $branchId,
                'branch' => [
                    'name' => $branch ? $branch->name : '',
                ],
                'details' => $products,
                'is_generate' => true,
            ];
        } else {
            foreach ($data->details as $key => $value) {
                $value->name = $value->product ? $value->product->name : '';
            }
        }

        return $this->response($data);
    }

    /**
     * First Stock
     */
    private function firstStock($branchId, $datePast, $value)
    {
        return Closing::where('branch_id', $branchId)
            ->whereDate('created_at', $datePast)
            ->with(['products' => function ($query) use ($value) {
                $query->select('stock_real', 'closing_id')->where('product_id', $value->id);
            }])
            ->whereHas('products', function ($query) use ($value) {
                $query->where('product_id', $value->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Current Stock
     */
    private function currentStock($branchId, $value)
    {
        return ProductStock::select('stock')->where('branch_id', $branchId)->where('product_id', $value->id)->first();
    }

    /**
     * Two Oclock
     */
    private function twoOclock($branchId, $value, $date, $firstStock)
    {
        $orderProduct = OrderProduct::where('product_id', $value->id)->whereHas('orders', function ($query) use ($branchId, $date) {
            $query->where('branch_id', $branchId)->whereDate('created_at', $date)->whereTime('created_at', '<=', '14:00:00');
        })->get();

        $totalQty = 0;
        foreach ($orderProduct as $value) {
            $totalQty = $totalQty + $value->qty;
        }

        //jika jam 2 tidak habis semua, maka munculkan 0
        //akan dimunculkan jika habis sebut berdasarkan stok
        if ($totalQty < $firstStock) {
            return 0;
        }

        return $totalQty;
    }

    /**
     * Four Oclock
     */
    private function fourOclock($branchId, $value, $date, $firstStock)
    {
        //cek total order yang lebih dari jam 2
        $orderProduct = OrderProduct::where('product_id', $value->id)->whereHas('orders', function ($query) use ($branchId, $date) {
            $query->where('branch_id', $branchId)->whereDate('created_at', $date)->whereTime('created_at', '>', '14:00:00');
        })->get();

        $totalQty = 0;
        foreach ($orderProduct as $value) {
            $totalQty = $totalQty + $value->qty;
        }

        //cek total order yang kurang dari jam 2
        // $orderProduct = OrderProduct::where('product_id', $value->id)->whereHas('orders', function ($query) use ($branchId, $date) {
        //     $query->where('branch_id', $branchId)->whereDate('created_at', $date)->whereTime('created_at', '<=', '14:00:00');
        // })->get();

        // $totalQtyTwo = 0;
        // foreach ($orderProduct as $value) {
        //     $totalQtyTwo = $totalQtyTwo + $value->qty;
        // }

        // //jika semua stok habis jam 4, maka munculkan semuanya di jam 4
        // if ($totalQty >= $firstStock) {
        //     $totalQty = $totalQty;
        // } else {
        //     //jika tidak
        //     //ada yang terjual sebagian di jam 2 kurang

        // }

        // if ($totalQtyTwo >= $firstStock) {
        //     $totalQty = $totalQty + $totalQtyTwo;
        // }

        // if ($totalQtyTwo >= $firstStock) {
        //     $totalQty = 0;
        // }

        return $totalQty;
    }

    /**
     * Tomorrow Plan
     */
    private function tomorrowPlan($firstStock, $two_oclock, $four_oclock)
    {
        if ($firstStock == $two_oclock || ($two_oclock >= $firstStock && $four_oclock == 0)) {
            return round(240 / 100 * $two_oclock);
        }

        return round(200 / 100 * ($two_oclock + $four_oclock));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name', 'code')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'name')->available()->search($request)->orderBy('name')->get();
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
            'date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.product_category_id' => 'required|integer',
            'products.*.first_stock' => 'required|integer',
            'products.*.realization' => 'required|integer',
            'products.*.two_oclock' => 'required|integer',
            'products.*.four_oclock' => 'required|integer',
            'products.*.tomorrow_plan' => 'required|integer',
            'products.*.current_stock' => 'required|integer',
        ]);
        $data['branch_id'] = (isset($data['branch_id']) && $data['branch_id']) ? $data['branch_id'] : Auth::user()->branch_id;
        $cek = TargetPlan::where('branch_id', $data['branch_id'])->where('date', $data['date'])->count();
        if ($cek > 0) {
            return $this->response('Cabang sudah pernah submit rencana target di tanggal ' . $data['date'], 'error', 422);
        }

        $totalQty = 0;
        foreach ($data['products'] as $key => $value) {
            $totalQty = $totalQty + $value['tomorrow_plan'];
        }

        $totalGrind = $this->totalGrind($totalQty);

        $products = $data['products'];

        $data = DB::connection('mysql')->transaction(function () use ($data, $totalGrind) {
            $model = $this->model->create($data);
            foreach ($data['products'] as $key => $value) {
                $detail = $model->details()->create($value);

                if ($totalGrind == 1) {
                    $detail->grinds()->create([
                        'grind' => 1,
                        'total' => $value['tomorrow_plan'],
                    ]);
                } else {
                    //
                }
            }

            return $model;
        });

        if ($totalGrind > 1) {
            $this->splitGrind($totalGrind, $products, $data, $totalQty);
        }

        return $this->response($data);
    }

    /**
     * Grind
     */
    private function totalGrind($totalQty)
    {
        if ($totalQty < config('production.total_grind')) {
            $totalGrind = 1;
        } else {
            $totalGrind = ($totalQty / config('production.total_grind'));
        }

        $explode = explode('.', $totalGrind);
        $totalGrind = $explode[0];
        if (isset($explode[1])) {
            $totalGrind++;
        }

        return (int)$totalGrind;
    }

    /**
     * Split Grind
     */
    private function splitGrind($totalGrind, $products, $data, $totalQty)
    {
        $config = config('production.total_grind');
        $totalAll = 0;
        $skipProducts = [];
        for ($i = 1; $i <= $totalGrind; $i++) {
            $total = 0;
            $totalGrinds = 0;
            while ($total < ($config * count($products))) {
                foreach ($products as $key => $value) {
                    $targetDetail = TargetPlanDetail::where('target_plan_id', $data->id)->where('product_id', $value['product_id'])->first();
                    $targetPlanDetailId = $targetDetail ? $targetDetail->id : 0;
                    $grind = TargetPlanDetailGrind::where('target_plan_detail_id', $targetPlanDetailId)->where('grind', $i)->first();
                    $grindTotal = TargetPlanDetailGrind::where('target_plan_detail_id', $targetPlanDetailId)->sum('total');

                    if (is_null($grind)) {
                        if (!in_array($value['product_id'], $skipProducts)) {
                            if ($value['tomorrow_plan'] > 0) {
                                $targetDetail->grinds()->create([
                                    'grind' => $i,
                                    'total' => 1,
                                ]);
                                $totalGrinds = $totalGrinds + 1;
                                $totalAll = $totalAll + 1;
                            } else {
                                $targetDetail->grinds()->create([
                                    'grind' => $i,
                                    'total' => 0,
                                ]);
                            }
                        } else {
                            $targetDetail->grinds()->create([
                                'grind' => $i,
                                'total' => 0,
                            ]);
                        }
                    } else {
                        if (!in_array($value['product_id'], $skipProducts)) {
                            if ((($grindTotal) < $value['tomorrow_plan']) && ($totalGrinds < $config) && ($totalAll < $totalQty)) {
                                //jika gilingan tidak melebih target produksi
                                //total gilingan keseluruhan produk dibawah 216
                                //total gilingan keseluruhan giligan < total jumlah produksi
                                $grind->update([
                                    'grind' => $i,
                                    'total' => $grind->total + 1,
                                ]);

                                $totalGrinds = $totalGrinds + 1;
                                $totalAll = $totalAll + 1;
                            } else {
                                if (($grindTotal + 1) > $value['tomorrow_plan']) {
                                    $skipProducts[] = $value['product_id'];
                                }
                            }
                        }
                    }

                    $total = $total + 1;
                }
            }
        }
    }
}
