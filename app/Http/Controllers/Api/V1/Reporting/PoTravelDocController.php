<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\PoTravelDoc;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Management\BranchService;
use App\Services\Reporting\PoTravelDocService;
use Maatwebsite\Excel\Facades\Excel;

class PoTravelDocController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:po-surat-jalan.lihat', [
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
        $data = PoTravelDocService::getAll($request);
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
        $branch = $request->branch_id;

        $fileName = 'realisasi po surat jalan-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new PoTravelDoc($startDate, $branch, $endDate), $fileName);
    }
}
