<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\Expense;
use App\Exports\Reporting\MutationStock;
use App\Exports\Reporting\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use App\Services\Management\TerritoryService;
use App\Services\Reporting\MutationStockService;
use App\Services\Reporting\StockService;
use Maatwebsite\Excel\Facades\Excel;

class MutationStockController extends Controller
{
    protected $productService;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ProductService $productService)
    {
        $this->middleware('permission:histori-mutasi-stok.lihat', [
            'only' => ['index']
        ]);
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = MutationStockService::getAll($request);
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
    public function listProduct()
    {
        $data = $this->productService->getAll(true, null, ['id', 'name']);
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
        $productId = $request->product_id;
        $branchId = $request->branch_id;

        $fileName = 'history mutasi stock-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new MutationStock($startDate, $branchId, $productId), $fileName);
    }
}
