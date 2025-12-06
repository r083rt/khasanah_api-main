<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Reporting\OrderService;
use Maatwebsite\Excel\Facades\Excel;

class HistoryOrderController extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:histori-pesanan.lihat', [
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
        $data = OrderService::getAll($request);
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

        $fileName = 'history pesanan pusat-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new Order($startDate, $endDate), $fileName);
    }
}
