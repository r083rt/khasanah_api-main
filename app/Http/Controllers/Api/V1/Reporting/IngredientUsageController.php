<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\IngredientUsageExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Services\Management\BranchService;
use App\Services\Reporting\IngredientUsageService;
use Maatwebsite\Excel\Facades\Excel;

class IngredientUsageController extends Controller
{
    protected $ingredientUsageService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(IngredientUsageService $ingredientUsageService)
    {
        $this->middleware('permission:pemakaian-bahan.lihat', [
            'only' => ['index', 'export', 'listBranch']
        ]);
        $this->ingredientUsageService = $ingredientUsageService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $checking = $this->ingredientUsageService->checking($request);
        if ($checking === false) {
            return $this->response('Data masih dalam proses perhitungan. Mohon tunggu beberapa saat', 'error', 422);
        }
        $data = IngredientUsageService::getAll($request);

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
    public function listCategory(Request $request)
    {
        $data = ProductCategory::select('id', 'name')->search($request)->orderBy('name')->get();
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
        $product_category_id = $request->product_category_id;

        if ($branch) {
            $branchIds = [$branch];
        } else {
            $branchIds = Branch::select('id')->pluck('id');
        }

        $fileName = 'pemakaian bahan-' . $startDate . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new IngredientUsageExport($startDate, $branchIds, $endDate, $product_category_id), $fileName);
    }
}
