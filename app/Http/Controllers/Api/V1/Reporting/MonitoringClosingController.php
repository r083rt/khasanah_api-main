<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\MonitoringClosingCookie;
use App\Exports\Reporting\MonitoringClosingDifferenceStock;
use App\Exports\Reporting\MonitoringClosingSummary;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Management\BranchService;
use App\Services\Reporting\MonitoringClosingService;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringClosingController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:monitor-closing.lihat', [
            'only' => ['index', 'export', 'listBranch']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = MonitoringClosingService::getAll($request);
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function differenceClosing(Request $request)
    {
        $data = MonitoringClosingService::getDifferenceCLosing($request);
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function targetCookie(Request $request)
    {
        $data = MonitoringClosingService::getTargetCookie($request);
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
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        $fileName = 'Monitoring Selisih Closing Summary-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new MonitoringClosingSummary($date, $branchId, $endDate), $fileName);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function exportDifferenceStock(Request $request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        MonitoringClosingService::getDifferenceCLosing($request);

        $fileName = 'Monitoring Selisih Closing Difference Stock-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new MonitoringClosingDifferenceStock($date, $branchId, $endDate), $fileName);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function exportTargetCookie(Request $request)
    {
        $date = $request->date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        $fileName = 'Monitoring Selisih Closing Target Cookie-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new MonitoringClosingCookie($date, $branchId, $endDate), $fileName);
    }
}
