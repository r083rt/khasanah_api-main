<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\Patokan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PatokanService;
use Maatwebsite\Excel\Facades\Excel;

class PatokanController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:beli-patokan.lihat', [
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
        $data = PatokanService::getAll($request);
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

        $fileName = 'beli-Patokan' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new Patokan($startDate, $endDate), $fileName);
    }
}
