<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\ReportRecipe as ReportingReportRecipe;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\PackagingRecipe;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\Reporting\ReportRecipe;
use Maatwebsite\Excel\Facades\Excel;

class ReportRecipeController extends Controller
{
    protected $model;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ReportRecipe $model)
    {
        $this->middleware('permission:laporan-resep.lihat', [
            'only' => ['index']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $product_ingredient_id = $request->product_ingredient_id;
        $search = $request->search;
        $data = $this->model->select('*');

        $master_packaging_id = $request->master_packaging_id;
        if ($master_packaging_id) {
            $data = $data->where('master_packaging_id', $master_packaging_id)->whereNull('product_ingredient_id');
        } elseif ($product_ingredient_id) {
            $data = $data->where('product_ingredient_id', $product_ingredient_id);
        }

        if ($search) {
            $data = $data->where(function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%')
                    ->orWhere('product_code', 'like', '%' . $search . '%')
                    ->orWhere('ingredient_name', 'like', '%' . $search . '%')
                    ->orWhere('ingredient_code', 'like', '%' . $search . '%');
            });
        }

        $data = $data->orderBy('product_name')->get();

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listIngredient()
    {
        $data = ProductIngredient::select('id', 'name')->orderBy('name')->get();
        $packaging = Packaging::select('id', 'name')->orderBy('name')->get();
        foreach ($packaging as $value) {
            $value->id = '9999' . (string)$value->id;
            $value->is_packaging = true;
            $value->name = $value->name . ' (Master Paketan)';
            $data = $data->push($value);
        }
        $data = $data->sortBy('name')->values();
        return $this->response($data);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $product_ingredient_id = $request->product_ingredient_id;
        $master_packaging_id = $request->master_packaging_id;
        $search = $request->search;

        $fileName = 'Report Resep-' . rand(0, 1000) . '.csv';
        return Excel::download(new ReportingReportRecipe($product_ingredient_id, $master_packaging_id, $search), $fileName);
    }
}
