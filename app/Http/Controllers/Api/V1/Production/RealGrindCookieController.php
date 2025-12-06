<?php

namespace App\Http\Controllers\Api\V1\Production;

use App\Exports\Reporting\RealGrindCookieTotal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory\ProductStockLog;
use App\Models\Product;
use App\Models\Production\RealGrindCookie;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RealGrindCookieController extends Controller
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
    public function __construct(RealGrindCookie $model, BranchService $branchService)
    {
        $this->middleware('permission:real-giling-roti-manis.lihat|real-giling-roti-manis.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-roti-manis.tambah', [
            'only' => ['store', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-roti-manis.ubah', [
            'only' => ['update', 'listBranch']
        ]);
        $this->middleware('permission:real-giling-roti-manis.hapus', [
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
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;
        $data = $this->model->where('date', '>=', $date)->where('date', '<=', $endDate)->with(['createdBy:id,name'])->search($request)->sort($request);
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

        $result = [];
        $date = $this->dateRange($date, $endDate);
        foreach ($date as $row) {
            foreach ($branchId as $value) {
                $data = $this->model->where('date', $row)->with(['createdBy:id,name'])->where('branch_id', $value->id)->get();
                $totalPressBread = $data->where('type', 'bread')->sum('total_press');
                $totalPressCookie = $data->where('type', 'cookie')->sum('total_press');
                $gramPressBread = $totalPressBread * 36 * 50;
                $gramPressCookie = $totalPressCookie * 36 * 50;
                $totalIncomingBread = $this->totalIncoming($value->id, $row, 'bread');
                $totalIncomingCookie = $this->totalIncoming($value->id, $row, 'cookie');
                $totalGramBread = $data->where('type', 'bread')->sum('gram_unit');
                $totalGramCookie = $data->where('type', 'cookie')->sum('gram_unit') + $gramPressCookie;

                $result[] =  [
                    'date' => $row,
                    'branch_id' => $value->id,
                    'branch_name' => $value->name,
                    'type' => 'ROTI TAWAR',
                    'total_grind' => $data->where('type', 'bread')->sum('grind_unit'),
                    'result_grind' => $data->where('type', 'bread')->sum('total_product'),
                    'total_press' => $totalPressBread,
                    'gram_press' => $gramPressBread,
                    'gram' => $totalGramBread,
                    'total_gram' => $data->where('type', 'bread')->sum('gram_unit'),
                    'adjustment' => $totalIncomingBread - $totalGramBread,
                    'total_incoming' => $totalIncomingBread
                ];

                $result[] = [
                    'date' => $row,
                    'branch_id' => $value->id,
                    'branch_name' => $value->name,
                    'type' => 'ROTI MANIS',
                    'total_grind' => $data->where('type', 'cookie')->sum('grind_unit'),
                    'result_grind' => $data->where('type', 'cookie')->sum('total_product'),
                    'total_press' => $totalPressCookie,
                    'gram_press' => $gramPressCookie,
                    'gram' => $totalGramCookie,
                    'total_gram' => $data->where('type', 'cookie')->sum('gram_unit'),
                    'adjustment' => $totalIncomingCookie - $totalGramCookie,
                    'total_incoming' => $totalIncomingCookie
                ];
            }
        }

        return $this->response($result);
    }

    /**
     * Total Incoming
     *
     * @param integer $branchId
     * @param string $date
     * @return integer
     */
    public function totalIncoming($branchId, $date, $type)
    {
        if ($type == 'cookie') {
            $categoryIds = config('production.cookie_categories');
        } else {
            $categoryIds = config('production.bread_categories');
        }

        $productIds = Product::whereIn('product_category_id', $categoryIds)->pluck('id');
        $from = [
            'Po Produksi Roti Manis',
            'Transfer Stok',
            'Penyesuain Stok',
            'Po Manual'
        ];

        $data = ProductStockLog::select('id', 'branch_id', 'product_id', 'stock', 'from', 'created_by', 'created_at')
            ->with(['product:id,name,code,product_category_id,gramasi'])
            ->whereIn('from', $from)
            ->whereIn('product_id', $productIds)
            ->where('stock', '!=', 0)
            ->whereDate('created_at', $date)
            // ->whereDate('created_at', '<=', $endDate)
            ->where('branch_id', $branchId)
            ->get();

        $totalGramasi = 0;
        foreach ($data as $value) {
            $gramasi = $value->product ? $value->product->gramasi : null;
            $totalGramasi = $totalGramasi + ($value->stock * $gramasi);
        }

        return $totalGramasi;
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
            'type' => 'required|in:cookie,bread',
            'grind_to' => 'required|integer',
            'grind_unit' => 'required|numeric',
            'total_press' => 'required_if:type,bread|numeric',
            'gram_unit' => 'required|integer',
            'total_product' => 'nullable|integer',
            'note' => 'nullable|string',
        ]);

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
        $model = $this->model->findOrFail($id);
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
            'grind_unit' => 'required|numeric',
            'total_press' => 'required_if:type,bread|numeric',
            'gram_unit' => 'required|integer',
            'total_product' => 'nullable|integer',
        ]);

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

        $fileName = 'real giling total-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new RealGrindCookieTotal($date, $branchId, $endDate), $fileName);
    }
}
