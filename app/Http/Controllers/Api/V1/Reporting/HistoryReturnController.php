<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\HistoryReturn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Management\BranchService;
use App\Services\Reporting\ReturService;
use Maatwebsite\Excel\Facades\Excel;

class HistoryReturnController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:histori-retur.lihat', [
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
        $data = ReturService::getAll($request);
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
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branchId = $request->branch_id;

        $fileName = 'history return dan sumbangan-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new HistoryReturn($startDate, $branchId, $endDate), $fileName);
    }
}
