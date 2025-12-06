<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use App\Services\Production\BrowniesTargetPlanReportService;
use Illuminate\Support\Facades\Auth;

class BrowniesTargetPlanReportController extends Controller
{
    /**
     * The user repository instance.
     */

    protected $branchService;

    protected $productService;

    protected $browniesTargetPlanReportService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(BranchService $branchService, ProductService $productService, BrowniesTargetPlanReportService $browniesTargetPlanReportService)
    {
        $this->middleware('permission:produksi-brownies-laporan.lihat|produksi-brownies-laporan.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->branchService = $branchService;
        $this->productService = $productService;
        $this->browniesTargetPlanReportService = $browniesTargetPlanReportService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $branchId = $request->branch_id;
        $date = $request->date;
        $day = date_to_day($date);
        if (empty($branchId) && Auth::user()->branch_id != 1) {
            $branchId = Auth::user()->branch_id;
        }

        $products = $this->browniesTargetPlanReportService->getReport($date, $branchId, $day);

        return $this->response($products);
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
}
