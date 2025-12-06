<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Management\BranchService;
use App\Services\Management\TerritoryService;
use App\Services\Reporting\SaleService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SaleController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:penjualan.lihat', [
            'only' => ['index']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = SaleService::getAll($request);
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = BranchService::getAll($request);
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listTerritory()
    {
        $data = TerritoryService::getAll();
        return $this->response($data);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;
        $customer = $request->customer_id;
        $territory = $request->territory_id;

        if ($territory) {
            if ($branch) {
                $branchIds = [$branch];
            } else {
                $branchIds = Branch::select('id')->where('territory_id', $territory)->pluck('id');
            }
        } else {
            $branchIds = null;
        }

        $fileName = 'history penjualan-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new Sale($startDate, $branchIds, $customer, $endDate), $fileName);
    }
}
