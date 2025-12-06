<?php

namespace App\Http\Controllers\Api\V1\Production;

use App\Exports\Reporting\RealGrindBrowniesStoreTotal;
use App\Exports\Reporting\RealGrindCookieTotal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductStockAdjustment;
use App\Models\Inventory\ProductStockLog;
use App\Models\Product;
use App\Models\Production\RealGrindBrowniesStore;
use App\Models\Production\RealGrindCookie;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RealGrindBrowniesStoreController extends Controller
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
    public function __construct(RealGrindBrowniesStore $model, BranchService $branchService)
    {
        $this->middleware('permission:real-giling-brownies-toko.lihat|real-giling-brownies-toko.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:real-giling-brownies-toko.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:real-giling-brownies-toko.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:real-giling-brownies-toko.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:real-giling-brownies-toko.lihat|real-giling-brownies-toko.show|real-giling-brownies-toko.tambah|real-giling-brownies-toko.ubah', [
            'only' => ['listBranch']
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
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;
        $data = $this->model->where('date', '>=', $date)->where('date', '<=', $endDate)->with(['createdBy:id,name', 'branch:id,name', 'packaging:id,name'])->search($request)->sort($request);
        if ($branchId) {
            $data = $data->where('branch_id', $branchId);
        } else {
            if (Auth::user()->branch_id != 1) {
                $data = $data->where('branch_id', Auth::user()->branch_id);
            }
        }
        $data = $data->paginate($this->perPage($data));

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch()
    {
        return $this->response($this->branchService->getAll());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listMasterPackaging(Request $request)
    {
        return $this->response(Packaging::where('type', $request->type)->get());
    }

    public function dateRange($from, $to)
    {
        return array_map(function($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;
        if ($branchId) {
            $branchId =  Branch::select('id', 'name')->where('id', $branchId)->get();
        } else {
            if (Auth::user()->branch_id != 1) {
                $branchId =  Branch::select('id', 'name')->where('id', Auth::user()->branch_id)->get();
            } else {
                $branchId =  Branch::select('id', 'name')->get();
            }
        }

        $paketan = Packaging::with(['products'])->get();

        $result = [];
        $date = $this->dateRange($date, $endDate);
        foreach ($date as $row) {
            $data = $this->model->where('date', $row)->get();
            foreach ($branchId as $value) {
                $data = $data->where('branch_id', $value->id);
                foreach ($paketan as $packaging) {
                    $productIds = $packaging->products->pluck('id');
                    $totalGramasiPackaging = $packaging->gramasi;

                    switch ($packaging->type) {
                        case 'brownies':
                            $type = 'BROWNIES';
                            break;

                        case 'sponge':
                            $type = 'BOLU';
                            break;

                        case 'cake':
                            $type = 'CAKE';
                            break;

                        default:
                            $type = null;
                            break;
                    }

                    $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($row)));
                    $totalIncoming = $this->totalIncoming($dateNext, $value->id, $productIds)['gramasi'];
                    $totalQty = $this->totalIncoming($dateNext, $value->id, $productIds)['qty'];

                    $qtyEstimation = $data->where('master_packaging_id', $packaging->id)->sum('qty_estimation');
                    $totalGrind = $data->where('master_packaging_id', $packaging->id)->sum('grind_unit');
                    $totalGramasiPackaging = $totalGramasiPackaging * $totalGrind;
                    $adjustment = $totalIncoming - ($totalGramasiPackaging);

                    $totalPcs = 0;
                    if ($totalGramasiPackaging != 0) {
                        $totalPcs = $adjustment / $totalGramasiPackaging;
                    }

                    $result[] =  [
                        'branch_name' => $value->name,
                        'date' => $row,
                        'type' => $type,
                        'detil_item' => $packaging->name,
                        'total_grind' => $totalGrind,
                        'result_grind' => $data->where('master_packaging_id', $packaging->id)->sum('qty_real'),
                        'gramasi' => $totalGramasiPackaging,
                        'qty_estimation' => $qtyEstimation,
                        'qty_real' => $totalQty,
                        'total_incoming' => $totalIncoming,
                        'adjustment' => $adjustment,
                        'total_pcs' => rounding_real_grind($totalPcs),
                    ];
                }
            }
        }

        return $this->response($result);
    }

    public function totalIncoming($date, $branchId, $productIds)
    {
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual',
            'Po Brownis',
            'Po Brownis Toko'
        ];

        $data = ProductStockLog::select('id', 'product_id', 'stock')
            ->with(['product:id,name,gramasi'])
            ->whereIn('from', $from)
            ->whereIn('product_id', $productIds)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', $date)
            ->where('branch_id', $branchId)
            ->get();

        $qty = 0;
        $totalGramasi = 0;
        foreach ($data as $value) {
            $gramasi = $value->product ? $value->product->gramasi : null;
            $gramasi_conversion = $value->stock * $gramasi;

            $qty += $value->stock;
            $totalGramasi += $gramasi_conversion;
        }

        return [
            'qty' => $qty,
            'gramasi' => $totalGramasi,
        ];
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
            'type' => 'required|in:brownies,sponge,cake',
            'master_packaging_id' => 'required|exists:master_packagings,id',
            'grind_to' => 'required|integer',
            'grind_unit' => 'required|numeric',
            'qty_real' => 'nullable|integer',
            'note' => 'nullable|string',
        ]);

        $masterPackaging = Packaging::find($data['master_packaging_id']);
        $data['qty_estimation'] = $data['grind_unit'] * $masterPackaging->grinds;
        $data['gramasi'] = $data['grind_unit'] * $masterPackaging->gramasi;

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->create($data);
        });

        return $this->response($data ? true : false);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->with(['packaging:id,name'])->findOrFail($id);
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
            'date' => 'required|date',
            'type' => 'required|in:brownies,sponge,cake',
            'master_packaging_id' => 'required|exists:master_packagings,id',
            'grind_to' => 'required|integer',
            'grind_unit' => 'required|numeric',
            'qty_real' => 'nullable|integer',
            'note' => 'nullable|string',
        ]);

        $masterPackaging = Packaging::find($data['master_packaging_id']);
        $data['qty_estimation'] = $data['grind_unit'] * $masterPackaging->grinds;
        $data['gramasi'] = $data['grind_unit'] * $masterPackaging->gramasi;

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            return $model->update($data);
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
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        $fileName = 'real giling brownies toko total-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new RealGrindBrowniesStoreTotal($date, $branchId, $endDate), $fileName);
    }
}
