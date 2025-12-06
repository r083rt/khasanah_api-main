<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\CookieProductionStockJob;
use App\Jobs\IngredientUsage;
use App\Models\Branch;
use App\Models\Pos\Closing;
use App\Models\Product;
use App\Models\Production\CookieProduct;
use App\Models\Production\CookieProduction;
use App\Models\ProductStock;
use App\Models\Reporting\IngredientUsage as ReportingIngredientUsage;
use App\Models\Reporting\IngredientUsageStatus;
use App\Services\Inventory\StockService;
use App\Services\Management\BranchService;
use App\Services\Production\CookieProductionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CookieProductionController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $cookieProductionService;

    protected $branchService;

    protected $stockService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(CookieProduct $model, CookieProductionService $cookieProductionService, BranchService $branchService, StockService $stockService)
    {
        $this->middleware('permission:roti-manis-po.lihat|roti-manis-po.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-po.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-po.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:roti-manis-po.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
        $this->cookieProductionService = $cookieProductionService;
        $this->branchService = $branchService;
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $date = $request->date;
        if ($branchId = $request->branch_id) {
            $datas = $this->cookieProductionService->getProduction($date, $branchId);
            if ($datas['is_editable'] === false) {
                $cek = $this->cookieProductionService->checkDataCalculating($date, $branchId);
                if ($cek > 0) {
                    return $this->response('Data masih dalam proses perhitungan. Mohon tunggu beberapa saat', 'error', 422);
                }
            }

            $cek = Closing::select('id')->where('branch_id', $branchId)->whereDate('created_at', $date)->count();
            if ($cek == 0) {
                return $this->response('Anda belum melakukan Closing. Silahkan Closing terlebih dahulu', 'error', 422);
            }

            return $this->response($datas);
        }

        return $this->response([
            'is_editable' => false,
            'data' => [],
        ]);
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
            'branch_id' => 'required|exists:branches,id',
            'datas' => 'required|array',
            'datas.*.product_id' => 'required|exists:products,id',
            'datas.*.product_name' => 'required|string',
            'datas.*.target' => 'required|integer',
            'datas.*.buffer' => 'required|integer',
            'datas.*.order' => 'required|integer',
            'datas.*.remains' => 'required|integer',
            'datas.*.total_target' => 'required|integer',
            'datas.*.total_target_after_remains' => 'required|integer',
            'datas.*.total_grinds' => 'required|integer',
            'datas.*.real_grinds' => 'required',
        ]);
        // set_time_limit(0);
        $cek = $this->cookieProductionService->checkData($data['date'], $data['branch_id']);
        if ($cek->count() > 1) {
            return $this->response('Tanggal PO ' . $data['date'] . ' Produksi sudah disubmit', 'error', 422);
        }

        $cek = Closing::select('id')->where('branch_id', $data['branch_id'])->whereDate('created_at', $data['date'])->count();
        if ($cek == 0) {
            return $this->response('Anda belum melakukan Closing. Silahkan Closing terlebih dahulu', 'error', 422);
        }

        try {
            DB::beginTransaction();
            $data['branch_name'] = Branch::find($data['branch_id'])->name;
            $totalGrinds = $data['datas'][0]['total_grinds'];
            $date = $data['date'];
            $branchId = $data['branch_id'];
            $realGrind = $data['datas'][0]['real_grinds'];

            $totalQty = 0;
            foreach ($data['datas'] as $value) {
                $totalQty = $totalQty + $value['total_target_after_remains'];
            }


            foreach ($data['datas'] as $value) {
                $value['branch_id'] = $data['branch_id'];
                $value['branch_name'] = $data['branch_name'];
                $value['date'] = $data['date'];
                $data = $this->cookieProductionService->create($value);
            }

            if ($totalGrinds > 0) {
                $this->cookieProductionService->calculateGrind($totalGrinds, $realGrind, $date, $branchId, $totalQty);

                // $allData = [
                //     'date' => $date,
                //     'branch_id' => $branchId,
                //     'created_by' => Auth::id(),
                // ];
                // dispatch(new CookieProductionStockJob($allData));

                $datas = CookieProduction::select('id', 'product_id')->with(['grinds'])->where([
                    'date' => $date,
                    'branch_id' => $branchId,
                ])->orderBy('total_target_after_remains', 'DESC')->get();
                $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($date)));
                foreach ($datas as $value) {
                    $total = $value->grinds ? $value->grinds->sum('total') : 0;
                    $stockService = app(StockService::class);
                    $stockService->create($value->product_id, $branchId, $total, 'Po Produksi Roti Manis', 'cookie_productions', $value->id, $dateNext, Auth::id());
                }
            }
            $this->cookieProductionService->updateStatus($data['date'], $data['branch_id'], 'completed');
            IngredientUsageStatus::updateOrCreate(
                [
                    'date' => $data['date'],
                    'branch_id' => $branchId,
                ],
                [
                    'date' => $data['date'],
                    'branch_id' => $branchId,
                    'status_po_production_cookie' => 'new',
                ]
            );
            dispatch(new IngredientUsage($date, $branchId, 'cookie'));
            DB::commit();
            return $this->response($data ? true : false);
        } catch (\Exception) {
            DB::rollBack();
            return $this->response('Ada kendala pada server, silahkan coba kembali', 'error', 422);
        }
    }
}
