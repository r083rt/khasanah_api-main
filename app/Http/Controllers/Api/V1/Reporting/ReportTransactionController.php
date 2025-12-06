<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\ReportTransaction;
use App\Exports\Reporting\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Management\BranchService;
use App\Services\Management\TerritoryService;
use App\Services\Reporting\ReportTransactionService;
use App\Services\Reporting\SaleService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportTransactionController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:report-transaksi.lihat', [
            'only' => ['indexx']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = ReportTransactionService::getAll($request);
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
        // ini_set('memory_limit', '-1');
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branch = $request->branch_id;
        $customer = $request->customer_id;
        $territory = $request->territory_id;

        $data = ReportTransactionService::getAll($request);

        $fileName = 'report-transactions-' . $startDate . '-' . $endDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new ReportTransaction($request), $fileName);
    }
}
